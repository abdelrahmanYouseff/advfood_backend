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
        // Try to find Python binary (venv first, then python3, then python)
        // Run: cd python && python3 scrap_zyda.py
        $scriptName = basename($scriptPath);
        $pythonDir = dirname($scriptPath);

        // Check for virtual environment first
        $venvPython = $pythonDir . '/venv/bin/python3';
        $venvPythonAlt = $pythonDir . '/venv/bin/python';

        $pythonBinary = null;

        // Priority 1: Try venv/bin/python3
        if (file_exists($venvPython)) {
            $pythonBinary = $venvPython;
            Log::info('🔍 Found venv Python binary', ['path' => $pythonBinary]);
        }
        // Priority 2: Try venv/bin/python
        elseif (file_exists($venvPythonAlt)) {
            $pythonBinary = $venvPythonAlt;
            Log::info('🔍 Found venv Python binary (alt)', ['path' => $pythonBinary]);
        }
        // Priority 3: Try system python3
        else {
            $pythonBinary = 'python3';
            Log::info('🔍 Using system python3');
        }

        Log::info('🔍 Running script', [
            'script' => $scriptName,
            'directory' => $pythonDir,
            'python_binary' => $pythonBinary,
            'command' => "cd {$pythonDir} && {$pythonBinary} {$scriptName}",
        ]);

        try {
            return $this->executeProcess($pythonBinary, $scriptName, $pythonDir);
        } catch (ProcessFailedException $e) {
            // Fallback to python if python3 command not found (for local)
            if ($this->isCommandNotFound($e) && $pythonBinary !== 'python') {
                Log::warning('Primary Python binary failed, fallback to python', [
                    'failed_binary' => $pythonBinary,
                    'script' => $scriptName,
                ]);
                return $this->executeProcess('python', $scriptName, $pythonDir);
            }
            throw $e;
        }
    }

    protected function executeProcess(string $binary, string $scriptName, string $workingDir): string
    {
        set_time_limit(600);

        Log::info('🚀 Starting Python script execution', [
            'command' => "{$binary} {$scriptName}",
            'working_dir' => $workingDir,
            'script_exists' => file_exists($workingDir . '/' . $scriptName),
        ]);

        // Verify script exists
        $scriptPath = $workingDir . '/' . $scriptName;
        if (!file_exists($scriptPath)) {
            $error = "Script file does not exist: {$scriptPath}";
            Log::error('❌ Script file not found', ['path' => $scriptPath]);
            throw new \RuntimeException($error);
        }

        // Run: cd python && python3 scrap_zyda.py
        // Set environment variables to ensure proper execution
        $env = [
            'PATH' => getenv('PATH'),
            'PYTHONUNBUFFERED' => '1', // Ensure Python output is not buffered
        ];

        // If using venv, add venv/bin to PATH
        if (strpos($binary, 'venv') !== false) {
            $venvBin = dirname($binary);
            $env['PATH'] = $venvBin . ':' . getenv('PATH');
            $env['VIRTUAL_ENV'] = dirname($venvBin);
            Log::info('🔧 Using virtual environment', [
                'venv_bin' => $venvBin,
                'venv_path' => $env['VIRTUAL_ENV'],
            ]);
        }

        // Create process: cd to python directory and run python script
        $process = new Process([$binary, $scriptName], $workingDir, $env);
        $process->setTimeout(600); // 10 minutes
        $process->setIdleTimeout(600); // 10 minutes idle

        Log::info('▶️ Executing process...', [
            'command' => "{$binary} {$scriptName}",
            'working_dir' => $workingDir,
            'env' => $env,
            'binary_exists' => file_exists($binary),
        ]);

        // Capture output in real-time
        $realTimeOutput = '';

        try {
            $process->start();
            Log::info('🔄 Process started (PID: ' . $process->getPid() . '), waiting for output...');

            // Read output in real-time
            while ($process->isRunning()) {
                // Get incremental output
                $newOutput = $process->getIncrementalOutput();
                $newError = $process->getIncrementalErrorOutput();

                if (!empty($newOutput)) {
                    $realTimeOutput .= $newOutput;
                    // Log each line
                    $lines = explode("\n", $newOutput);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            Log::info('📤 Python stdout', ['output' => $line]);
                        }
                    }
                }

                if (!empty($newError)) {
                    Log::warning('📥 Python stderr', ['output' => trim($newError)]);
                }

                // Small sleep to avoid busy waiting (reduced for faster response)
                usleep(50000); // 0.05 seconds (reduced from 0.1)
            }

            // Get any remaining output
            $remainingOutput = $process->getOutput();
            $remainingError = $process->getErrorOutput();

            if (!empty($remainingOutput)) {
                $realTimeOutput .= $remainingOutput;
            }

            Log::info('⏸️ Process finished', [
                'exit_code' => $process->getExitCode(),
                'running' => $process->isRunning(),
                'output_length' => strlen($realTimeOutput),
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Process execution error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'exit_code' => $process->getExitCode(),
                'running' => $process->isRunning(),
            ]);

            // Try to get output even if there was an error
            if (!$process->isRunning()) {
                $realTimeOutput .= $process->getOutput();
            }
            throw $e;
        }

        $output = trim($process->getOutput());
        $errorOutput = trim($process->getErrorOutput());
        $exitCode = $process->getExitCode();

        // If we captured real-time output, prefer it over getOutput()
        // (getOutput() might be empty if output was flushed to callback)
        if (!empty($realTimeOutput)) {
            $output = trim($realTimeOutput) ?: $output;
        }

        // Combine output and error output for display
        $fullOutput = $output;
        if (!empty($errorOutput)) {
            $fullOutput .= "\n[STDERR]\n" . $errorOutput;
        }

        Log::info('📋 Python script execution completed', [
            'exit_code' => $exitCode,
            'output_length' => strlen($output),
            'error_output_length' => strlen($errorOutput),
            'full_output_length' => strlen($fullOutput),
            'is_successful' => $process->isSuccessful(),
            'output_preview' => substr($output, 0, 500), // First 500 chars
        ]);

        if (! empty($errorOutput)) {
            Log::warning('⚠️ Python script stderr output', [
                'error_output' => $errorOutput,
            ]);
        }

        if (! $process->isSuccessful()) {
            Log::error('❌ Python script failed', [
                'exit_code' => $exitCode,
                'error_output' => $errorOutput,
                'output' => substr($output, 0, 500), // First 500 chars
            ]);
            throw new ProcessFailedException($process);
        }

        // Log if output is empty
        if (empty($output) && empty($errorOutput)) {
            Log::warning('⚠️ Python script returned empty output (both stdout and stderr are empty)');
            $fullOutput = '[WARN] السكريبت لم يطبع أي output. قد يكون هناك مشكلة في السكريبت.';
        }

        return $fullOutput ?: '[INFO] لا يوجد output من السكريبت';
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

