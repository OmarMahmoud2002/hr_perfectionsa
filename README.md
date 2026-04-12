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
3) ثبّت الاعتمادات:
```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
4) أذونات الكتابة لـ `storage/` و `bootstrap/cache/`.
5) في الإنتاج: `APP_DEBUG=false`, `QUEUE_CONNECTION=sync` (مناسب للاستضافة المشتركة)، وحدد بيانات SMTP وقاعدة البيانات في `.env`.

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
