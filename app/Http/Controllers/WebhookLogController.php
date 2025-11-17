<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class WebhookLogController extends Controller
{
    /**
     * Display webhook logs from laravel.log file
     */
    public function index(Request $request)
    {
        $logFile = storage_path('logs/laravel.log');
        $lines = (int) $request->get('lines', 1000); // Read more lines to find webhooks
        
        $webhooks = [];
        
        if (File::exists($logFile)) {
            $fileSize = File::size($logFile);
            
            // Read log file
            if ($fileSize > 10 * 1024 * 1024) { // أكبر من 10MB
                $command = "tail -n {$lines} " . escapeshellarg($logFile);
                $fileContent = shell_exec($command);
                $allLines = explode("\n", $fileContent);
            } else {
                $fileContent = File::get($logFile);
                $allLines = explode("\n", $fileContent);
                $allLines = array_reverse($allLines);
                $selectedLines = array_slice($allLines, 0, $lines);
                $allLines = array_reverse($selectedLines);
            }
            
            // Combine lines into blocks (each log entry can span multiple lines)
            $currentBlock = '';
            $inWebhookBlock = false;
            
            foreach ($allLines as $line) {
                // Check if this line starts a new log entry
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] /', $line)) {
                    // Save previous block if it's a webhook
                    if ($inWebhookBlock && !empty($currentBlock)) {
                        $webhooks[] = $this->parseWebhookBlock($currentBlock);
                    }
                    $currentBlock = $line;
                    $inWebhookBlock = stripos($line, 'Generic Webhook') !== false || stripos($line, '🔔 Generic Webhook') !== false;
                } else {
                    // Continue current block
                    if ($inWebhookBlock) {
                        $currentBlock .= "\n" . $line;
                    }
                }
            }
            
            // Save last block
            if ($inWebhookBlock && !empty($currentBlock)) {
                $webhooks[] = $this->parseWebhookBlock($currentBlock);
            }
            
            // Reverse to show newest first
            $webhooks = array_reverse($webhooks);
        }
        
        return view('webhooks.index', compact('webhooks', 'lines'));
    }
    
    /**
     * Parse a webhook log block and extract structured data
     */
    private function parseWebhookBlock($block)
    {
        // Extract timestamp
        $timestamp = null;
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $block, $matches)) {
            $timestamp = $matches[1];
        }
        
        // Try to extract JSON data from the log
        $data = [];
        $headers = [];
        $contentType = null;
        $method = null;
        $url = null;
        $ipAddress = null;
        
        // Look for JSON structure in the log - try multiple patterns
        // Pattern 1: "all_data": {...}
        if (preg_match('/"all_data":\s*(\{.*?\})/s', $block, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if ($jsonData) {
                $data = $jsonData;
            }
        }
        
        // Pattern 2: "json_content": {...}
        if (empty($data) && preg_match('/"json_content":\s*(\{.*?\})/s', $block, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if ($jsonData) {
                $data = $jsonData;
            }
        }
        
        // Pattern 3: "form_data": {...}
        if (empty($data) && preg_match('/"form_data":\s*(\{.*?\})/s', $block, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if ($jsonData) {
                $data = $jsonData;
            }
        }
        
        // Pattern 4: Try to find any JSON object in the block
        if (empty($data) && preg_match('/\{[^{}]*"phone"[^{}]*\}/', $block, $matches)) {
            $jsonData = json_decode($matches[0], true);
            if ($jsonData && json_last_error() === JSON_ERROR_NONE) {
                $data = $jsonData;
            }
        }
        
        // Extract other fields
        if (preg_match('/"content_type":\s*"([^"]*)"/', $block, $matches)) {
            $contentType = $matches[1];
        }
        
        if (preg_match('/"method":\s*"([^"]*)"/', $block, $matches)) {
            $method = $matches[1];
        }
        
        if (preg_match('/"url":\s*"([^"]*)"/', $block, $matches)) {
            $url = $matches[1];
        }
        
        if (preg_match('/"ip_address":\s*"([^"]*)"/', $block, $matches)) {
            $ipAddress = $matches[1];
        }
        
        return [
            'timestamp' => $timestamp,
            'method' => $method,
            'url' => $url,
            'content_type' => $contentType,
            'ip_address' => $ipAddress,
            'data' => $data,
            'raw_block' => $block, // Keep raw block for full details
        ];
    }
    
    /**
     * API endpoint to get webhooks as JSON
     */
    public function api(Request $request)
    {
        $logFile = storage_path('logs/laravel.log');
        $limit = (int) $request->get('limit', 50);
        
        $webhooks = [];
        
        if (File::exists($logFile)) {
            $command = "tail -n 5000 " . escapeshellarg($logFile);
            $fileContent = shell_exec($command);
            $allLines = explode("\n", $fileContent);
            
            $currentBlock = '';
            $inWebhookBlock = false;
            
            foreach ($allLines as $line) {
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] /', $line)) {
                    if ($inWebhookBlock && !empty($currentBlock)) {
                        $webhooks[] = $this->parseWebhookBlock($currentBlock);
                    }
                    $currentBlock = $line;
                    $inWebhookBlock = stripos($line, 'Generic Webhook') !== false || stripos($line, '🔔 Generic Webhook') !== false;
                } else {
                    if ($inWebhookBlock) {
                        $currentBlock .= "\n" . $line;
                    }
                }
            }
            
            if ($inWebhookBlock && !empty($currentBlock)) {
                $webhooks[] = $this->parseWebhookBlock($currentBlock);
            }
            
            $webhooks = array_reverse($webhooks);
            $webhooks = array_slice($webhooks, 0, $limit);
        }
        
        return response()->json([
            'success' => true,
            'count' => count($webhooks),
            'webhooks' => $webhooks,
        ]);
    }
}

