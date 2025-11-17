<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GenericWebhookController extends Controller
{
    /**
     * Handle generic webhook requests.
     * This webhook accepts any type of data and logs it for inspection.
     */
    public function handle(Request $request)
    {
        // Get all request data
        $allData = $request->all();
        
        // Get raw content
        $rawContent = $request->getContent();
        
        // Get JSON content if available
        $jsonContent = null;
        if ($request->isJson()) {
            $jsonContent = $request->json()->all();
        }
        
        // Get form data
        $formData = $request->except(['_token', '_method']);
        
        // Get headers
        $headers = $request->headers->all();
        
        // Get request method
        $method = $request->method();
        
        // Get full URL
        $url = $request->fullUrl();
        
        // Get content type
        $contentType = $request->header('Content-Type');
        
        // Log all information
        Log::info('🔔 Generic Webhook Received', [
            'method' => $method,
            'url' => $url,
            'content_type' => $contentType,
            'all_data' => $allData,
            'json_content' => $jsonContent,
            'form_data' => $formData,
            'raw_content' => $rawContent,
            'headers' => $headers,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
        
        // Also log to a separate file for easier inspection
        Log::channel('daily')->info('Generic Webhook Data', [
            'method' => $method,
            'content_type' => $contentType,
            'all_data' => $allData,
            'json_content' => $jsonContent,
            'form_data' => $formData,
            'raw_content' => $rawContent,
            'headers' => $headers,
        ]);
        
        // Return success response with received data summary
        return response()->json([
            'status' => 'success',
            'message' => 'Webhook received successfully',
            'received' => [
                'method' => $method,
                'content_type' => $contentType,
                'data_keys' => array_keys($allData),
                'has_json' => !empty($jsonContent),
                'has_form_data' => !empty($formData),
                'has_raw_content' => !empty($rawContent),
                'headers_count' => count($headers),
            ],
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}

