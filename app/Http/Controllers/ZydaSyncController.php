<?php

namespace App\Http\Controllers;

use App\Services\ZydaScriptRunner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZydaSyncController extends Controller
{
    public function __construct(protected ZydaScriptRunner $runner)
    {
    }

    /**
     * Trigger the Zyda scraping script manually.
     */
    public function __invoke(Request $request)
    {
        Log::info('🔄 Starting Zyda manual sync...', [
            'request_id' => $request->header('X-Request-ID'),
            'user_agent' => $request->header('User-Agent'),
        ]);
        $startTime = microtime(true);
        
        try {
            Log::info('📞 Calling ZydaScriptRunner->run()...');
            $result = $this->runner->run();
            
            $summary = $result['summary'] ?? null;
            $output = $result['output'] ?? '';
            
            $duration = round(microtime(true) - $startTime, 2);
            
            Log::info('✅ Zyda sync completed', [
                'duration_seconds' => $duration,
                'summary' => $summary,
                'output_length' => strlen($output),
                'output_preview' => substr($output, 0, 200), // First 200 chars
            ]);

            // If output is empty, add a warning message
            if (empty($output)) {
                Log::warning('⚠️ Python script returned empty output');
                $output = '[WARN] السكريبت لم يطبع أي output. قد يكون هناك مشكلة في السكريبت.\nيرجى التحقق من السكريبت يدوياً.';
            }

            $message = 'تمت مزامنة طلبات Zyda بنجاح.';
            if ($summary) {
                $message .= sprintf(
                    ' تم إنشاء %d وتحديث %d وتخطي %d (فشل %d).',
                    $summary['created'],
                    $summary['updated'],
                    $summary['skipped'],
                    $summary['failed'],
                );
            }

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'summary' => $summary,
                    'output' => $output ?: '[INFO] لا يوجد output من السكريبت', // Ensure output is never null
                    'duration' => $duration,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            $duration = round(microtime(true) - $startTime, 2);
            
            $errorOutput = $e->getMessage();
            $processOutput = '';
            $processError = '';
            
            if (method_exists($e, 'getProcess') && $e->getProcess()) {
                $process = $e->getProcess();
                $processOutput = $process->getOutput();
                $processError = $process->getErrorOutput();
                $errorOutput .= "\n[STDOUT]\n" . $processOutput;
                $errorOutput .= "\n[STDERR]\n" . $processError;
            }
            
            Log::error('❌ Zyda manual sync failed', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'duration_seconds' => $duration,
                'process_output' => $processOutput,
                'process_error' => $processError,
            ]);

            $errorMessage = 'فشل مزامنة طلبات Zyda: ' . $e->getMessage();

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => $e->getMessage(),
                    'output' => $errorOutput, // Include error output
                    'duration' => $duration,
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }
}

