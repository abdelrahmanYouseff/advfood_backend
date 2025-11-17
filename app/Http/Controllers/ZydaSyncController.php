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
        try {
            $result = $this->runner->run();
            $summary = $result['summary'] ?? null;
            $output = $result['output'] ?? '';

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

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'summary' => $summary,
                    'output' => $output,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Zyda manual sync failed', [
                'error' => $e->getMessage(),
            ]);

            $errorMessage = 'فشل مزامنة طلبات Zyda: ' . $e->getMessage();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }
}

