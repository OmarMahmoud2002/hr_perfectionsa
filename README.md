## Attendance System (Laravel 10 + Vite)

منظومة حضور وانصراف ورواتب بواجهة عربية، تدعم استيراد ملفات Excel، حساب الحضور، التقارير الشهرية، وإدارة الإعدادات.

### المتطلبات
- PHP 8.2 أو أحدث، Composer
- Node.js 20 + npm
- MySQL 8 (أو متوافق)
- امتدادات PHP: `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `zip`

### التثبيت السريع (محلي)
```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
# إنشاء حساب مدير سريعاً
php artisan tinker --execute="\\App\\Models\\User::create(['name'=>'Admin','email'=>'admin@perfection.com','password'=>bcrypt('123456789'),'role'=>'admin']);"
```

### تشغيل التطبيق
- الخادم: `php artisan serve`
- الواجهة: `npm run dev` أثناء التطوير أو `npm run build` للإنتاج.

### الاختبارات
```bash
php artisan test
```

### استيراد ملفات الحضور
- الصيغ المدعومة: `.xlsx`, `.xls` (حتى 10MB).
- الأعمدة المطلوبة: رقم الموظف، الاسم، التاريخ، وقت الدخول، وقت الخروج.
- بعد الرفع يمكن ضبط إعدادات الشهر وإضافة الإجازات قبل التأكيد.

### توليد بيانات اختبار كبيرة (أداء)
```bash
php artisan tinker --execute="\\App\\Models\\Employee::factory()->count(500)->create();"
# يمكن استخدام AttendanceRecordFactory لتوليد سجلات حضور إضافية
```

### النشر على GoDaddy (استضافة مشتركة)
1) اجعل جذر الموقع يشير إلى مجلد `public/`.
2) فعّل PHP 8.2+ من لوحة التحكم.
3) اضبط ملف `.env` للإنتاج (مهم جدًا لتجنب خطأ 419):
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=your-domain.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```
إذا كنت تستخدم `www`، ثبّت خيارًا واحدًا فقط (إما `www` أو بدونه) وأعد توجيه الآخر له.
4) شغّل الترحيلات (يتضمن جدول `sessions`):
```bash
php artisan migrate --force
```
5) امسح كاش Laravel بعد أي تعديل في `.env`:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
6) أذونات الكتابة للمجلدات:
- `storage/`
- `bootstrap/cache/`

بدون صلاحيات كتابة للجلسات سيظهر 419 حتى لو `@csrf` صحيح.
7) ثبّت الاعتمادات وابنِ الأصول:
```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan key:generate
php artisan storage:link
```
8) في الإنتاج: `QUEUE_CONNECTION=sync` (مناسب للاستضافة المشتركة)، وحدد بيانات SMTP وقاعدة البيانات في `.env`.

### تشخيص سريع لخطأ 419 بعد النشر
- جرّب تسجيل الدخول من نافذة خاصة (Incognito) بعد مسح الكوكيز.
- تأكد أن عنوان الصفحة و `APP_URL` بنفس البروتوكول والدومين (HTTPS + نفس الدومين).
- راجع آخر أخطاء Laravel:
```bash
tail -n 100 storage/logs/laravel.log
```

### نقاط أمان سريعة
- استخدم كلمات مرور قوية للحسابات الإدارية وفعّل البريد للتنبيهات.
- راقب حدود الرفع (`upload_max_filesize`, `post_max_size`).
- احتفظ بـ `APP_KEY` سرياً، ولا ترفع ملف `.env` للمستودع.

### توثيق الصلاحيات
- مرجع الصلاحيات ومسارات الوصول الحساسة موجود في: `docs/access-control-rules.md`.
- عند أي تعديل في الصلاحيات أو المسارات، يجب تحديث الوثيقة وتشغيل اختبارات الصلاحيات.

### هيكل مختصر
- `app/Http/Controllers` — منطق الحضور، الاستيراد، الرواتب، الإعدادات.
- `app/Services` — الحسابات ومعالجة Excel.
- `resources/views` — واجهة Blade عربية (Tailwind + Vite).
- `tests/Feature` — اختبارات إدارة الموظفين والإعدادات.
