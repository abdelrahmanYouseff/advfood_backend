<!DOCTYPE html>
<html lang="ar" dir="rtl" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حمل تطبيق AdvFood - iOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Cairo', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9fafb;
        }
        .glass-card {
            backdrop-filter: blur(12px);
            background: #ffffff;
            border: 1px solid rgba(203, 213, 225, 0.9);
        }
        .iphone-frame {
            background: radial-gradient(circle at top, #f3f4f6, #e5e7eb);
        }
    </style>
</head>
<body class="text-black">
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="max-w-5xl w-full grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
            <!-- Text / CTA -->
            <div class="space-y-6">
                <a href="https://apps.apple.com/sa/app/advfood/id6754232982"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-400/30 text-emerald-600 text-xs hover:bg-emerald-500/15">
                    <i class="fa-brands fa-apple text-sm"></i>
                    <span>متوفر الآن على iPhone</span>
                </a>

                <h1 class="text-3xl md:text-4xl font-bold leading-snug">
                    حمل تطبيق <span class="text-emerald-500">AdvFood</span> الآن
                    <span class="block text-lg md:text-xl mt-3 text-black font-normal">
                        اطلب من مطاعمك المفضلة بسهولة، وتتبع طلباتك مباشرة من جوالك.
                    </span>
                </h1>

                <ul class="space-y-3 text-sm md:text-base text-black">
                    <li class="flex items-start gap-3">
                        <span class="mt-1 w-6 h-6 rounded-full bg-emerald-500/15 flex items-center justify-center text-emerald-300">
                            <i class="fas fa-bolt text-xs"></i>
                        </span>
                        <span>تجربة سريعة وسلسة لطلب الطعام في ثوانٍ.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 w-6 h-6 rounded-full bg-emerald-500/15 flex items-center justify-center text-emerald-300">
                            <i class="fas fa-map-location-dot text-xs"></i>
                        </span>
                        <span>تتبع حالة طلبك وموقع التوصيل لحظة بلحظة.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 w-6 h-6 rounded-full bg-emerald-500/15 flex items-center justify-center text-emerald-300">
                            <i class="fas fa-bell text-xs"></i>
                        </span>
                        <span>إشعارات فورية عند تجهيز الطلب وخروجه للتوصيل.</span>
                    </li>
                </ul>

                <div class="space-y-3">
                    <!-- App Store button -->
                    <a href="https://apps.apple.com/sa/app/advfood/id6754232982"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-3 px-5 py-3 rounded-2xl bg-white text-slate-900 hover:bg-slate-100 transition shadow-lg shadow-emerald-500/20">
                        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-black text-white">
                            <i class="fa-brands fa-apple text-xl"></i>
                        </div>
                        <div class="text-right leading-tight">
                            <div class="text-[11px] text-slate-500">تحميل من</div>
                            <div class="text-sm font-semibold">App Store</div>
                        </div>
                    </a>

                    <p class="text-xs text-black">
                        * رابط التطبيق أعلاه توضيحي – قم بتحديثه برابط App Store الفعلي عند توفره.
                    </p>
                </div>

                <div class="pt-4 text-xs text-black">
                    مدعوم بواسطة <span class="font-semibold text-emerald-500">AdvFood</span>
                </div>
            </div>

            <!-- Phone Preview -->
            <div class="flex justify-center">
                <div class="glass-card rounded-3xl p-5 w-full max-w-sm shadow-2xl">
                    <div class="iphone-frame rounded-3xl p-3 border border-slate-700/70 relative overflow-hidden">
                        <!-- Notch -->
                        <div class="absolute top-2 left-1/2 -translate-x-1/2 w-32 h-5 bg-black rounded-full z-10"></div>

                        <div class="bg-slate-950/80 rounded-2xl h-[460px] p-5 flex flex-col justify-between">
                            <!-- App header -->
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-400 to-sky-500 flex items-center justify-center shadow-lg">
                                        <img src="{{ asset('images/logo.svg') }}"
                                             alt="AdvFood"
                                             class="w-7 h-7 object-contain"
                                             onerror="this.src='{{ asset('images/logo.svg') }}'">
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-slate-50">AdvFood</div>
                                        <div class="text-[11px] text-emerald-300">تطبيق طلب الطعام</div>
                                    </div>
                                </div>

                                <div class="mt-3 space-y-2">
                                    <div class="flex items-center justify-between text-xs text-slate-300">
                                        <span>أقرب المطاعم إليك</span>
                                        <span class="flex items-center gap-1 text-emerald-300">
                                            <i class="fas fa-location-dot text-[10px]"></i>
                                            <span>الرياض</span>
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-3 gap-2 text-[11px]">
                                        <div class="rounded-xl bg-slate-900/70 border border-slate-700/80 p-2.5 space-y-1">
                                            <div class="h-9 mb-1.5 rounded-lg overflow-hidden flex items-center justify-center bg-white/0">
                                                <img src="{{ asset('images/GatherUs-Logo.png') }}"
                                                     alt="GatherUs"
                                                     class="w-full h-full object-contain"
                                                     loading="lazy"
                                                     onerror="this.style.display='none'; this.parentElement.style.backgroundColor='#0f172a';">
                                            </div>
                                            <div class="font-semibold text-slate-50 truncate text-center">Gather Us</div>
                                        </div>
                                        <div class="rounded-xl bg-slate-900/70 border border-slate-700/80 p-2.5 space-y-1">
                                            <div class="h-9 mb-1.5 rounded-lg overflow-hidden flex items-center justify-center bg-white/0">
                                                <img src="{{ asset('images/Screenshot 1447-06-12 at 4.33.48 PM.png') }}"
                                                     alt="Tant Bakiza"
                                                     class="w-full h-full object-cover"
                                                     loading="lazy"
                                                     onerror="this.style.display='none'; this.parentElement.style.backgroundColor='#312e81';">
                                            </div>
                                            <div class="font-semibold text-slate-50 truncate text-center">Tant Bakiza</div>
                                        </div>
                                        <div class="rounded-xl bg-slate-900/70 border border-slate-700/80 p-2.5 space-y-1">
                                            <div class="h-9 mb-1.5 rounded-lg overflow-hidden flex items-center justify-center bg-white/0">
                                                <img src="{{ asset('images/Screenshot 1447-06-12 at 4.36.30 PM.png') }}"
                                                     alt="Delawa"
                                                     class="w-full h-full object-cover"
                                                     loading="lazy"
                                                     onerror="this.style.display='none'; this.parentElement.style.backgroundColor='#0ea5e9';">
                                            </div>
                                            <div class="font-semibold text-slate-50 truncate text-center">Delawa</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom CTA inside phone -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-[11px] text-slate-600">
                                    <span>سجل دخولك وتابع طلباتك بسهولة.</span>
                                    <span class="flex items-center gap-1 text-emerald-500">
                                        <i class="fas fa-bell text-[10px]"></i>
                                        <span>إشعارات فورية</span>
                                    </span>
                                </div>
                                <a href="https://apps.apple.com/sa/app/advfood/id6754232982"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="w-full inline-flex items-center justify-center py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-xs font-semibold text-slate-900 transition">
                                    ابدأ الآن من تطبيق iOS
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


