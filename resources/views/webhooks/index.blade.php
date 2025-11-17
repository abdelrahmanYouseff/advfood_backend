<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض الـ Webhooks - AdvFood</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        .json-view {
            background-color: #1e293b;
            color: #e2e8f0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }
        .json-key {
            color: #60a5fa;
        }
        .json-string {
            color: #34d399;
        }
        .json-number {
            color: #fbbf24;
        }
        .json-boolean {
            color: #f87171;
        }
        .json-null {
            color: #9ca3af;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">🔔 عرض الـ Webhooks</h1>
                        <p class="text-gray-600 mt-1">عرض جميع البيانات المستلمة على الـ webhook</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('webhooks.index', ['lines' => request('lines', 500)]) }}"
                           class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                            🔄 تحديث
                        </a>
                        <a href="{{ route('logs.index', ['filter' => 'Generic Webhook']) }}"
                           class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                            📋 عرض في الـ Logs
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ count($webhooks) }}</div>
                        <div class="text-gray-600">عدد الـ Webhooks</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $lines }}</div>
                        <div class="text-gray-600">عدد الأسطر المفحوصة</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ request('lines', 500) }}</div>
                        <div class="text-gray-600">عدد الأسطر المطلوبة</div>
                    </div>
                </div>
            </div>

            <!-- Webhooks List -->
            <div class="space-y-6">
                @if(count($webhooks) > 0)
                    @foreach($webhooks as $index => $webhook)
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">
                                        🔔 Webhook #{{ count($webhooks) - $index }}
                                    </h2>
                                    <p class="text-gray-600 text-sm mt-1">
                                        {{ $webhook['timestamp'] ?? 'تاريخ غير متوفر' }}
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    @if($webhook['method'])
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                            {{ $webhook['method'] }}
                                        </span>
                                    @endif
                                    @if($webhook['content_type'])
                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                                            {{ $webhook['content_type'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Basic Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                @if($webhook['url'])
                                    <div>
                                        <div class="text-sm font-semibold text-gray-700 mb-1">URL:</div>
                                        <div class="text-sm text-gray-900 bg-gray-50 p-2 rounded break-all">{{ $webhook['url'] }}</div>
                                    </div>
                                @endif
                                @if($webhook['ip_address'])
                                    <div>
                                        <div class="text-sm font-semibold text-gray-700 mb-1">IP Address:</div>
                                        <div class="text-sm text-gray-900 bg-gray-50 p-2 rounded">{{ $webhook['ip_address'] }}</div>
                                    </div>
                                @endif
                            </div>

                            <!-- Data -->
                            @if(!empty($webhook['data']))
                                <div class="mb-4">
                                    <div class="text-sm font-semibold text-gray-700 mb-2">البيانات المستلمة:</div>
                                    <div class="json-view">
                                        <pre id="json-{{ $index }}">{{ json_encode($webhook['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            @endif

                            <!-- Raw Block (Collapsible) -->
                            <details class="mt-4">
                                <summary class="cursor-pointer text-sm font-semibold text-gray-700 hover:text-gray-900 mb-2">
                                    📄 عرض السجل الكامل (Raw Log)
                                </summary>
                                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg mt-2 overflow-x-auto">
                                    <pre class="text-xs whitespace-pre-wrap font-mono">{{ $webhook['raw_block'] }}</pre>
                                </div>
                            </details>
                        </div>
                    @endforeach
                @else
                    <div class="bg-white rounded-lg shadow-md p-12 text-center">
                        <div class="text-6xl mb-4">📭</div>
                        <p class="text-xl text-gray-600 mb-2">لا توجد webhooks لعرضها</p>
                        <p class="text-gray-500">تأكد من:</p>
                        <ul class="text-gray-500 mt-2 text-right max-w-md mx-auto">
                            <li>• إرسال بيانات إلى <code class="bg-gray-100 px-2 py-1 rounded">POST /api/webhook/generic</code></li>
                            <li>• زيادة عدد الأسطر المفحوصة إذا كانت الـ webhooks قديمة</li>
                            <li>• التحقق من وجود ملف الـ log في <code class="bg-gray-100 px-2 py-1 rounded">storage/logs/laravel.log</code></li>
                        </ul>
                        <div class="mt-6">
                            <a href="{{ route('webhooks.index', ['lines' => 5000]) }}"
                               class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition inline-block">
                                🔍 فحص 5000 سطر
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination/More Lines -->
            @if(count($webhooks) > 0)
                <div class="mt-6 bg-white rounded-lg shadow-md p-4 text-center">
                    <p class="text-gray-600 mb-3">لرؤية المزيد من الـ webhooks:</p>
                    <div class="flex gap-2 justify-center flex-wrap">
                        <a href="{{ route('webhooks.index', ['lines' => 1000]) }}"
                           class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                            1000 سطر
                        </a>
                        <a href="{{ route('webhooks.index', ['lines' => 5000]) }}"
                           class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                            5000 سطر
                        </a>
                        <a href="{{ route('webhooks.index', ['lines' => 10000]) }}"
                           class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                            10000 سطر
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>

