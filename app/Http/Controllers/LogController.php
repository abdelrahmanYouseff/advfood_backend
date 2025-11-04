<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    public function index(Request $request)
    {
        // التحقق من الصلاحيات - يمكنك إضافة middleware للتحقق من الصلاحيات
        // if (!auth()->check() || !auth()->user()->is_admin) {
        //     abort(403, 'Unauthorized');
        // }

        $logFile = storage_path('logs/laravel.log');
        $lines = (int) $request->get('lines', 500); // عدد الأسطر الافتراضي 500
        $filter = $request->get('filter', ''); // تصفية حسب كلمة
        $level = $request->get('level', ''); // تصفية حسب المستوى (error, warning, info)

        $logs = [];
        $errorCount = 0;
        $warningCount = 0;
        $infoCount = 0;

        if (File::exists($logFile)) {
            $fileContent = File::get($logFile);
            $allLines = explode("\n", $fileContent);

            // عكس المصفوفة للحصول على آخر الأسطر
            $allLines = array_reverse($allLines);

            // أخذ عدد الأسطر المطلوبة
            $selectedLines = array_slice($allLines, 0, $lines);

            // عكس مرة أخرى للحصول على الترتيب الصحيح
            $selectedLines = array_reverse($selectedLines);

            foreach ($selectedLines as $line) {
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
        ];

        return view('logs.index', compact('logs', 'stats', 'lines', 'filter', 'level'));
    }

    public function clear()
    {
        $logFile = storage_path('logs/laravel.log');

        if (File::exists($logFile)) {
            File::put($logFile, '');
            Log::info('Log file cleared by user');
        }

        return redirect()->route('logs.index')->with('success', 'تم مسح الـ logs بنجاح');
    }

    public function download()
    {
        $logFile = storage_path('logs/laravel.log');

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

