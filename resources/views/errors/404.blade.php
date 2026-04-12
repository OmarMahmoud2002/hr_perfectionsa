<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 | الصفحة غير موجودة</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="w-full max-w-2xl rounded-3xl overflow-hidden shadow-2xl border border-slate-200 bg-white">
            <div class="px-8 py-7 text-white" style="background: radial-gradient(circle at 80% 10%, rgba(56,189,248,.25), transparent 45%), linear-gradient(130deg, #315f8f 0%, #28786f 100%);">
                <p class="text-xs uppercase tracking-[0.24em] text-white/75 mb-2">Error 404</p>
                <h1 class="text-3xl sm:text-4xl font-black">الصفحة غير موجودة</h1>
                <p class="text-sm text-white/85 mt-2">الرابط الذي حاولت الوصول إليه غير متاح أو تم نقله.</p>
            </div>

            <div class="p-8 space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm text-slate-600 leading-7">تأكد من صحة الرابط، أو ارجع إلى لوحة التحكم للمتابعة.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-bold text-white bg-slate-900 hover:bg-slate-700 transition">العودة للوحة التحكم</a>
                    <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-700 border border-slate-300 hover:bg-slate-100 transition">رجوع</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
