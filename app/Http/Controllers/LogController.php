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

        // Analyze shipping status from logs
        $shippingStatus = $this->analyzeShippingStatus($logFile);

        return view('logs.index', compact('logs', 'stats', 'lines', 'filter', 'level', 'shippingStatus'));
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

    /**
     * Analyze shipping status from logs
     * Extract information about orders sent to Shadda shipping company
     */
    private function analyzeShippingStatus($logFile)
    {
        $shippingAttempts = [];
        $currentAttempt = null;

        if (!File::exists($logFile)) {
            return [
                'attempts' => [],
                'summary' => [
                    'total' => 0,
                    'success' => 0,
                    'failed' => 0,
                ],
            ];
        }

        // Read last 2000 lines to find shipping attempts
        $fileSize = File::size($logFile);
        if ($fileSize > 10 * 1024 * 1024) {
            $command = "tail -n 2000 " . escapeshellarg($logFile);
            $fileContent = shell_exec($command);
            $allLines = explode("\n", $fileContent);
        } else {
            $fileContent = File::get($logFile);
            $allLines = explode("\n", $fileContent);
            $allLines = array_reverse($allLines);
            $selectedLines = array_slice($allLines, 0, 2000);
            $allLines = array_reverse($selectedLines);
        }

        foreach ($allLines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Detect start of shipping attempt
            if (stripos($line, 'SHADDASHIPPINGSERVICE::createOrder CALLED') !== false ||
                stripos($line, '📦 SHADDASHIPPINGSERVICE::createOrder CALLED') !== false) {
                // Extract order info
                preg_match('/order_id["\']?\s*=>\s*(\d+)/i', $line, $orderIdMatch);
                preg_match('/order_number["\']?\s*=>\s*([^\s,}]+)/i', $line, $orderNumberMatch);
                
                $currentAttempt = [
                    'order_id' => $orderIdMatch[1] ?? null,
                    'order_number' => $orderNumberMatch[1] ?? null,
                    'status' => 'processing',
                    'timestamp' => $this->extractTimestamp($line),
                    'error' => null,
                    'success' => false,
                ];
            }

            // Detect success
            if ($currentAttempt && (
                stripos($line, 'Order successfully sent to shipping company') !== false ||
                stripos($line, '🎉 Order successfully sent to shipping company') !== false ||
                stripos($line, '✅ Order successfully sent') !== false
            )) {
                $currentAttempt['status'] = 'success';
                $currentAttempt['success'] = true;
                
                // Extract dsp_order_id if available
                preg_match('/dsp_order_id["\']?\s*=>\s*([^\s,}]+)/i', $line, $dspMatch);
                $currentAttempt['dsp_order_id'] = $dspMatch[1] ?? null;
                
                $shippingAttempts[] = $currentAttempt;
                $currentAttempt = null;
            }

            // Detect failure
            if ($currentAttempt && (
                stripos($line, 'Failed to send order to shipping company') !== false ||
                stripos($line, '❌ Failed to send order to shipping company') !== false ||
                stripos($line, '🔴') !== false && stripos($line, 'shipping') !== false
            )) {
                $currentAttempt['status'] = 'failed';
                $currentAttempt['success'] = false;
                
                // Extract error message
                $errorMessage = $this->extractErrorMessage($line);
                if (empty($currentAttempt['error'])) {
                    $currentAttempt['error'] = $errorMessage;
                }
            }

            // Extract detailed error information
            if ($currentAttempt && $currentAttempt['status'] === 'failed') {
                if (stripos($line, 'http_status') !== false) {
                    preg_match('/http_status["\']?\s*=>\s*(\d+)/i', $line, $statusMatch);
                    if (isset($statusMatch[1])) {
                        $currentAttempt['http_status'] = $statusMatch[1];
                    }
                }
                
                if (stripos($line, 'error_message') !== false || stripos($line, 'message') !== false) {
                    $errorMsg = $this->extractErrorMessage($line);
                    if (!empty($errorMsg) && empty($currentAttempt['error'])) {
                        $currentAttempt['error'] = $errorMsg;
                    }
                }

                // Check for specific error types
                if (stripos($line, '422') !== false || stripos($line, 'Validation Error') !== false) {
                    $currentAttempt['error_type'] = 'Validation Error (422)';
                    if (stripos($line, 'branch') !== false || stripos($line, 'shop_id') !== false) {
                        $currentAttempt['error'] = 'خطأ في shop_id أو branchId - يرجى التحقق من shop_id في جدول المطاعم';
                    }
                } elseif (stripos($line, '401') !== false) {
                    $currentAttempt['error_type'] = 'Authentication Error (401)';
                    $currentAttempt['error'] = 'خطأ في المصادقة - يرجى التحقق من SHADDA_CLIENT_ID و SHADDA_SECRET_KEY';
                } elseif (stripos($line, '404') !== false) {
                    $currentAttempt['error_type'] = 'Not Found (404)';
                    $currentAttempt['error'] = 'Endpoint غير موجود - يرجى التحقق من SHADDA_API_URL';
                } elseif (stripos($line, 'Connection Exception') !== false || stripos($line, 'Connection') !== false) {
                    $currentAttempt['error_type'] = 'Connection Error';
                    $currentAttempt['error'] = 'خطأ في الاتصال - يرجى التحقق من الاتصال بالإنترنت أو عنوان API';
                }
            }

            // If we have a failed attempt and find a new attempt, save the failed one
            if ($currentAttempt && $currentAttempt['status'] === 'failed' && 
                (stripos($line, 'SHADDASHIPPINGSERVICE::createOrder CALLED') !== false)) {
                $shippingAttempts[] = $currentAttempt;
                $currentAttempt = null;
            }
        }

        // Save last attempt if still processing
        if ($currentAttempt) {
            $shippingAttempts[] = $currentAttempt;
        }

        // Get only last 20 attempts
        $shippingAttempts = array_slice(array_reverse($shippingAttempts), 0, 20);

        // Calculate summary
        $summary = [
            'total' => count($shippingAttempts),
            'success' => count(array_filter($shippingAttempts, fn($a) => $a['success'])),
            'failed' => count(array_filter($shippingAttempts, fn($a) => !$a['success'])),
        ];

        return [
            'attempts' => $shippingAttempts,
            'summary' => $summary,
        ];
    }

    private function extractTimestamp($line)
    {
        // Try to extract timestamp from log line
        // Format: [2024-01-01 12:00:00] or similar
        preg_match('/\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]/', $line, $match);
        return $match[1] ?? date('Y-m-d H:i:s');
    }

    private function extractErrorMessage($line)
    {
        // Try to extract error message from log line
        preg_match('/message["\']?\s*=>\s*["\']?([^"\']+)["\']?/i', $line, $match);
        if (isset($match[1])) {
            return trim($match[1]);
        }
        
        // Try alternative patterns
        preg_match('/error["\']?\s*=>\s*["\']?([^"\']+)["\']?/i', $line, $match);
        if (isset($match[1])) {
            return trim($match[1]);
        }

        return null;
    }
}

