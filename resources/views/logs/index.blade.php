<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض الـ Logs - AdvFood</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        .log-line {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.6;
            padding: 4px 8px;
            border-left: 3px solid;
            margin-bottom: 2px;
            word-break: break-all;
        }
        .log-error {
            background-color: #fee2e2;
            border-color: #dc2626;
            color: #991b1b;
        }
        .log-warning {
            background-color: #fef3c7;
            border-color: #d97706;
            color: #92400e;
        }
        .log-success {
            background-color: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        .log-info {
            background-color: #dbeafe;
            border-color: #2563eb;
            color: #1e40af;
        }
        .auto-refresh {
            animation: pulse 2s infinite;
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
                        <h1 class="text-3xl font-bold text-gray-900">📋 عرض الـ Logs</h1>
                        <p class="text-gray-600 mt-1">جميع الـ logs والـ errors على السيرفر</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('logs.index', ['refresh' => 'auto']) }}"
                           class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                            {{ request()->get('refresh') === 'auto' ? '⏸️ إيقاف التحديث التلقائي' : '🔄 تحديث تلقائي' }}
                        </a>
                        <a href="{{ route('logs.download') }}"
                           class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                            ⬇️ تحميل الـ Logs
                        </a>
                        <form action="{{ route('logs.clear') }}" method="POST" class="inline"
                              onsubmit="return confirm('هل أنت متأكد من مسح جميع الـ logs؟');">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                                🗑️ مسح الـ Logs
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-md p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">إجمالي الأسطر</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_lines'] }}</p>
                        </div>
                        <div class="text-3xl">📊</div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">الأخطاء</p>
                            <p class="text-2xl font-bold text-red-600">{{ $stats['errors'] }}</p>
                        </div>
                        <div class="text-3xl">❌</div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">التحذيرات</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ $stats['warnings'] }}</p>
                        </div>
                        <div class="text-3xl">⚠️</div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">معلومات</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $stats['info'] }}</p>
                        </div>
                        <div class="text-3xl">📋</div>
                    </div>
                </div>
            </div>

            <!-- File Info -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">حالة الملف:</span>
                        <span class="font-semibold {{ $stats['file_exists'] ? 'text-green-600' : 'text-red-600' }}">
                            {{ $stats['file_exists'] ? '✅ موجود' : '❌ غير موجود' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600">حجم الملف:</span>
                        <span class="font-semibold">{{ $stats['file_size'] }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">آخر تعديل:</span>
                        <span class="font-semibold">{{ $stats['last_modified'] }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Log Channel:</span>
                        <span class="font-semibold {{ $stats['log_channel'] === 'single' ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ $stats['log_channel'] ?? 'N/A' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600">Log Level:</span>
                        <span class="font-semibold">{{ $stats['log_level'] ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <form method="GET" action="{{ route('logs.index') }}" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">عدد الأسطر</label>
                        <input type="number" name="lines" value="{{ $lines }}" min="50" max="10000" step="50"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">البحث (كلمة مفتاحية)</label>
                        <input type="text" name="filter" value="{{ $filter }}" placeholder="shipping, error, order..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="min-w-[150px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">النوع</label>
                        <select name="level" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">الكل</option>
                            <option value="error" {{ $level === 'error' ? 'selected' : '' }}>أخطاء فقط</option>
                            <option value="warning" {{ $level === 'warning' ? 'selected' : '' }}>تحذيرات فقط</option>
                            <option value="info" {{ $level === 'info' ? 'selected' : '' }}>معلومات فقط</option>
                            <option value="success" {{ $level === 'success' ? 'selected' : '' }}>نجاح فقط</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                            🔍 بحث
                        </button>
                    </div>
                    <div>
                        <a href="{{ route('logs.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                            🔄 إعادة تعيين
                        </a>
                    </div>
                </form>
            </div>

            <!-- Success Message -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Logs Display -->
            <div class="bg-white rounded-lg shadow-md p-4">
                @if(count($logs) > 0)
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900">الـ Logs ({{ count($logs) }} سطر)</h2>
                        <button onclick="scrollToTop()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                            ⬆️ للأعلى
                        </button>
                    </div>
                    <div class="bg-gray-900 rounded-lg p-4 overflow-auto max-h-[600px]" id="logs-container">
                        @foreach($logs as $log)
                            <div class="log-line log-{{ $log['type'] }}">
                                <span class="mr-2">{{ $log['icon'] }}</span>
                                <span>{{ $log['line'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">📭</div>
                        <p class="text-xl text-gray-600">لا توجد logs لعرضها</p>
                        <p class="text-gray-500 mt-2">جرب تغيير الفلاتر أو عدد الأسطر</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function scrollToTop() {
            document.getElementById('logs-container').scrollTop = 0;
        }

        // Auto refresh if enabled
        @if(request()->get('refresh') === 'auto')
            setTimeout(function() {
                window.location.reload();
            }, 5000); // تحديث كل 5 ثواني
        @endif

        // Auto scroll to bottom on load
        window.addEventListener('load', function() {
            const container = document.getElementById('logs-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    </script>
</body>
</html>

