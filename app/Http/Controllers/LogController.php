<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    public function index(Request $request)
    {
        // Log access to logs page
        $user = $request->user();
        Log::info('📋 Logs page accessed', [
            'user_id' => $user?->id ?? 'guest',
            'user_name' => $user?->name ?? 'guest',
            'ip' => $request->ip(),
        ]);

        $logFile = storage_path('logs/laravel.log');
        $lines = (int) $request->get('lines', 500); // عدد الأسطر الافتراضي 500
        $filter = $request->get('filter', ''); // تصفية حسب كلمة
        $level = $request->get('level', ''); // تصفية حسب المستوى (error, warning, info)

        $logs = [];
        $errorCount = 0;
        $warningCount = 0;
        $infoCount = 0;

        if (File::exists($logFile)) {
            // استخدام tail command للأداء الأفضل مع الملفات الكبيرة
            $fileSize = File::size($logFile);

            // إذا كان الملف كبير جداً، استخدم tail command
            if ($fileSize > 10 * 1024 * 1024) { // أكبر من 10MB
                $command = "tail -n {$lines} " . escapeshellarg($logFile);
                $fileContent = shell_exec($command);
                $allLines = explode("\n", $fileContent);
            } else {
                // للملفات الصغيرة، استخدم الطريقة العادية
                $fileContent = File::get($logFile);
                $allLines = explode("\n", $fileContent);

                // عكس المصفوفة للحصول على آخر الأسطر
                $allLines = array_reverse($allLines);

                // أخذ عدد الأسطر المطلوبة
                $selectedLines = array_slice($allLines, 0, $lines);

                // عكس مرة أخرى للحصول على الترتيب الصحيح
                $allLines = array_reverse($selectedLines);
            }

            foreach ($allLines as $line) {
                if (empty(trim($line))) {
                    continue;
                }

                // تطبيق الفلاتر
                if (!empty($filter) && stripos($line, $filter) === false) {
                    continue;
                }

                // تحديد نوع الـ log
                $logType = 'info';
                $logIcon = '📋';
                $logColor = 'gray';

                if (stripos($line, 'error') !== false || stripos($line, '❌') !== false || stripos($line, '🔴') !== false) {
                    $logType = 'error';
                    $logIcon = '❌';
                    $logColor = 'red';
                    $errorCount++;
                } elseif (stripos($line, 'warning') !== false || stripos($line, '⚠️') !== false) {
                    $logType = 'warning';
                    $logIcon = '⚠️';
                    $logColor = 'yellow';
                    $warningCount++;
                } elseif (stripos($line, 'success') !== false || stripos($line, '✅') !== false) {
                    $logType = 'success';
                    $logIcon = '✅';
                    $logColor = 'green';
                    $infoCount++;
                } elseif (stripos($line, 'info') !== false || stripos($line, '🚀') !== false || stripos($line, '🔍') !== false) {
                    $logType = 'info';
                    $logIcon = '📋';
                    $logColor = 'blue';
                    $infoCount++;
                }

                // تطبيق filter المستوى
                if (!empty($level) && $logType !== $level) {
                    continue;
                }

                $logs[] = [
                    'line' => $line,
                    'type' => $logType,
                    'icon' => $logIcon,
                    'color' => $logColor,
                ];
            }
        }

        // إحصائيات
        $stats = [
            'total_lines' => count($logs),
            'errors' => $errorCount,
            'warnings' => $warningCount,
            'info' => $infoCount,
            'file_exists' => File::exists($logFile),
            'file_size' => File::exists($logFile) ? $this->formatBytes(File::size($logFile)) : '0 B',
            'last_modified' => File::exists($logFile) ? date('Y-m-d H:i:s', File::lastModified($logFile)) : 'N/A',
            'log_channel' => config('logging.default'),
            'log_level' => config('logging.channels.single.level', 'debug'),
        ];

        // Log if file doesn't exist
        if (!File::exists($logFile)) {
            Log::warning('⚠️ Log file does not exist', [
                'log_file_path' => $logFile,
                'log_channel' => config('logging.default'),
            ]);
        }

        return view('logs.index', compact('logs', 'stats', 'lines', 'filter', 'level'));
    }

    public function clear()
    {
        $logFile = storage_path('logs/laravel.log');

        $user = request()->user();
        Log::info('🗑️ Log file clear requested', [
            'user_id' => $user?->id ?? 'guest',
            'user_name' => $user?->name ?? 'guest',
            'ip' => request()->ip(),
        ]);

        if (File::exists($logFile)) {
            File::put($logFile, '');
            Log::info('✅ Log file cleared successfully');
        }

        return redirect()->route('logs.index')->with('success', 'تم مسح الـ logs بنجاح');
    }

    public function download()
    {
        $logFile = storage_path('logs/laravel.log');

        $user = request()->user();
        Log::info('⬇️ Log file download requested', [
            'user_id' => $user?->id ?? 'guest',
            'user_name' => $user?->name ?? 'guest',
            'ip' => request()->ip(),
        ]);

        if (!File::exists($logFile)) {
            abort(404, 'Log file not found');
        }

        return response()->download($logFile, 'laravel-' . date('Y-m-d') . '.log');
    }

    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}

