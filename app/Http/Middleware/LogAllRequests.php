<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogAllRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log request details
        $user = $request->user() ?? \Illuminate\Support\Facades\Auth::guard('branches')->user();
        Log::info('🌐 Incoming Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $user?->id ?? 'guest',
            'user_name' => $user?->name ?? 'guest',
            'request_data' => $this->sanitizeRequestData($request->all()),
            'headers' => $this->getRelevantHeaders($request),
        ]);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // in milliseconds

        // Log response details
        $user = $request->user() ?? \Illuminate\Support\Facades\Auth::guard('branches')->user();
        Log::info('✅ Request Completed', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => $user?->id ?? 'guest',
        ]);

        return $response;
    }

    /**
     * Sanitize sensitive data from request
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret', 'credit_card'];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '***HIDDEN***';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeRequestData($value);
            }
        }

        return $data;
    }

    /**
     * Get relevant headers
     */
    private function getRelevantHeaders(Request $request): array
    {
        $relevantHeaders = ['accept', 'content-type', 'authorization', 'referer', 'origin'];
        $headers = [];

        foreach ($relevantHeaders as $header) {
            if ($request->headers->has($header)) {
                $value = $request->headers->get($header);
                // Hide sensitive authorization data
                if ($header === 'authorization' && $value) {
                    $headers[$header] = substr($value, 0, 20) . '...';
                } else {
                    $headers[$header] = $value;
                }
            }
        }

        return $headers;
    }
}

