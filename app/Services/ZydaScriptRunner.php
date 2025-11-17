<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ZydaScriptRunner
{
    /**
     * Run the scraper script and return an array with output + summary data.
     */
    public function run(): array
    {
        $scriptPath = base_path('python/scrap_zyda.py');

        if (! file_exists($scriptPath)) {
            throw new \RuntimeException('ملف السكربت غير موجود في المسار python/scrap_zyda.py');
        }

        $output = $this->runScript($scriptPath);
        $summary = $this->parseSummary($output);

        return [
            'output' => $output,
            'summary' => $summary,
        ];
    }

    protected function runScript(string $scriptPath): string
    {
        $primary = env('ZYDA_PYTHON_BINARY', 'python');
        $fallback = 'python3';

        try {
            return $this->executeProcess($primary, $scriptPath);
        } catch (ProcessFailedException $e) {
            if ($this->isCommandNotFound($e) && $primary !== $fallback) {
                Log::warning('Primary python binary failed, fallback to python3', [
                    'binary' => $primary,
                ]);

                return $this->executeProcess($fallback, $scriptPath);
            }

            throw $e;
        }
    }

    protected function executeProcess(string $binary, string $scriptPath): string
    {
        set_time_limit(600);

        $process = new Process([$binary, $scriptPath], base_path());
        $process->setTimeout(600);
        $process->setIdleTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return trim($process->getOutput());
    }

    protected function isCommandNotFound(ProcessFailedException $e): bool
    {
        return $e->getProcess()->getExitCode() === 127;
    }

    protected function parseSummary(string $output): ?array
    {
        if (preg_match_all(
            '/SUMMARY\\s+created=(\\d+)\\s+updated=(\\d+)\\s+skipped=(\\d+)\\s+failed=(\\d+)/i',
            $output,
            $matches,
            PREG_SET_ORDER
        )) {
            $last = end($matches);

            return [
                'created' => (int) $last[1],
                'updated' => (int) $last[2],
                'skipped' => (int) $last[3],
                'failed' => (int) $last[4],
            ];
        }

        return null;
    }
}

