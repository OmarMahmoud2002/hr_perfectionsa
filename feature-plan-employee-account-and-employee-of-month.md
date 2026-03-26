# خطة تنفيذ فيتشر الحسابات للموظفين + فيتشر موظف الشهر

تاريخ الوثيقة: 2026-03-25
الحالة: تحليل وتجهيز قبل التنفيذ

## 1) تحليل الوضع الحالي في المشروع

- نظام الصلاحيات الحالي يعتمد على حقل role في users بقيمتين فقط: admin و viewer.
- التحقق من الصلاحية موجود في:
  - Middleware role عبر CheckRole.
  - Helper isAdmin داخل User.
  - شروط authorize داخل FormRequest (مثل StoreEmployeeRequest/UpdateEmployeeRequest).
- كيان Employee منفصل عن User حالياً (لا يوجد ربط مباشر employee_id داخل users).
- شاشات الموظفين الحالية لإدارة بيانات الموظف الوظيفية (ac_no, name, salary, shift...)، لكنها ليست حساب دخول الموظف.
- توجد صفحة Profile افتراضية (Breeze) لكنها غير مدمجة مع تصميم النظام الحالي ولا تحقق متطلبات الملف الشخصي المطلوبة.
- إحصائيات الحضور موجودة بالفعل ويمكن إعادة استخدامها من الخدمات الحالية، خصوصا:
  - AbsenceDetectionService
  - DashboardStatisticsService
- جدول attendance_records يحتوي work_minutes بالفعل، وهذا ممتاز لطلب عرض ساعات العمل.

النتيجة: البنية الحالية قوية لحساب الحضور، لكنها تحتاج طبقة حسابات موظفين/ملفات شخصية وصلاحيات أكثر تفصيلا، بالإضافة إلى موديول تصويت شهري مستقل.

---

## 2) تفكيك المتطلبات إلى موديولات

## 2.1 فيتشر 1: حساب لكل موظف + ملف شخصي + صلاحيات حسب الوظيفة

المطلوب الوظيفي:
- Admin ينشئ حسابات الموظفين (email + password ابتدائية).
- البريد الإلكتروني يُولّد تلقائيا بالصيغة: employee_slug@perfection.com (غير قابل للتعديل).
- كلمة السر الابتدائية لكل الحسابات: 123456789.
- أول دخول: الموظف يغير كلمة السر إجباريا.
- إضافة بيانات شخصية للموظف:
  - الصورة
  - bio
  - رابطين سوشيال ميديا
- إضافة الوظيفة (Job Title) من قائمة ثابتة:
  - مصمم
  - 3D
  - خدمة عملاء
  - مبرمج
  - HR
  - Admin
  - مدير
- المدير و HR لهم نفس صلاحيات admin.
- إذا الوظيفة = Admin يتم جعل role = admin.
- شاشة حساب الموظف تحتوي:
  - بيانات أساسية (الاسم غير قابل للتعديل، الوظيفة غير قابلة للتعديل إلا من Admin)
  - صورة + كلمة سر + Bio + روابط التواصل (قابلة للتعديل)
  - قسم إحصائيات شهري للقراءة فقط (لا تعديل)

## 2.2 فيتشر 2: نظام موظف الشهر (تصويت + مؤشرات)

المطلوب الوظيفي:
- كل موظف (باستثناء admin/manager/hr) يصوت مرة واحدة فقط شهريا حتى يوم 20 (قبل يوم 21).
- لا يمكن التصويت للنفس.
- لا يمكن التراجع بعد التصويت.
- المرشحون: الموظفون فقط (بدون admin/manager/hr).
- Admin يرى صفحة "موظف الشهر" وفيها:
  - نتائج تصويت الموظفين وعدد الأصوات.
  - أكثر موظف عمل ساعات (من Excel/attendance_records.work_minutes).
  - أكثر موظف يحضر بدري.
  - أكثر موظف عمل overtime.
  - تقييم admin اليدوي.
- تعديل تقرير الحضور: استبدال "نسبة الحضور" بـ "عدد ساعات العمل" لكل موظف.

---

## 3) التصميم المقترح (Data Model)

## 3.1 توصية مهمة: فصل "الوظيفة" عن "الصلاحية"

بدلا من الاعتماد على role فقط لكل شيء:
- role = صلاحية النظام (authorization)
- job_title = المسمى الوظيفي (business info)

سبب التوصية:
- قد يكون شخص "مبرمج" لكن يحتاج صلاحيات إدارية مؤقتة.
- يسهل التوسع مستقبلا بدون كسر منطق الصلاحيات.

## 3.2 تعديلات قاعدة البيانات (Feature 1)

1. users table
- إضافة employee_id (nullable + unique + FK -> employees.id) لربط الحساب بالموظف.
- توسيع role ليشمل على الأقل:
  - admin
  - employee
  - manager
  - hr
- إضافة must_change_password (boolean, default true للحسابات التي ينشئها admin).
- إضافة last_password_changed_at (nullable datetime).

2. employees table
- إضافة job_title (enum أو string constrained).
- يفضل إضافة عمود عربي ثابت أو enum values بالإنجليزية ثم mapping للعرض.

3. user_profiles table (جدول جديد)
- user_id (unique FK)
- avatar_path (nullable string)
- bio (nullable text)
- social_link_1 (nullable string 500)
- social_link_2 (nullable string 500)

ملاحظة:
- يمكن وضع هذه الحقول داخل users مباشرة، لكن جدول user_profiles أنظف للتوسع.

## 3.3 جداول جديدة (Feature 2)

1. employee_month_votes
- id
- voter_user_id (FK users)
- voted_employee_id (FK employees)
- vote_month (tinyint)
- vote_year (smallint)
- created_at
- unique(voter_user_id, vote_month, vote_year)  // تصويت واحد لكل شهر

2. employee_month_admin_scores
- id
- employee_id (FK employees)
- month
- year
- score (decimal or tinyint)
- note (nullable)
- created_by (FK users)
- unique(employee_id, month, year)

3. اختياريا: employee_month_snapshots
- لتخزين النتائج النهائية بعد إغلاق الشهر (يحسن الثبات والأرشفة).

---

## 4) تصميم الصلاحيات

## 4.1 قاعدة صلاحيات موحدة

- Admin = صلاحية كاملة.
- Manager = نفس صلاحية Admin.
- HR = نفس صلاحية Admin.
- Employee = صلاحية محدودة (ملفه الشخصي + إحصائياته + التصويت إن كان مؤهلا).

## 4.2 تنفيذ تقني

- تحديث User model:
  - isAdminLike(): true إذا role in [admin, manager, hr]
  - canVoteThisMonth(), isVotingEligible()
- تعديل Middleware role أو إنشاء middleware جديد مثل role.any.
- تحديث جميع authorize checks التي تعتمد فقط على isAdmin.

---

## 5) تصميم الشاشات المطلوبة

## 5.1 شاشات Feature 1

1. شاشة Admin لإنشاء/ربط حساب موظف
- ضمن شاشة إنشاء/تعديل الموظف أو شاشة منفصلة "حسابات الموظفين".
- حقول: email, password_initial, job_title, role_auto_rules.

2. شاشة "حسابي" للموظف
- تبويب 1: البيانات الأساسية
  - الاسم (readonly)
  - الوظيفة (readonly)
  - البريد (readonly)
  - الصورة
  - bio
  - social links
- تبويب 2: الأمان
  - تغيير كلمة السر
  - مؤشر إذا كانت أول مرة تسجيل
- تبويب 3: إحصائياتي الشهرية (readonly)
  - أيام الحضور
  - ساعات التأخير
  - ساعات overtime
  - جدول الحضور للشهر الحالي

3. تجربة أول دخول (First Login)
- إذا must_change_password=true:
  - منع الوصول لباقي الصفحات
  - تحويل إجباري إلى شاشة تغيير كلمة السر

## 5.2 شاشات Feature 2

1. واجهة الموظف: "اختيار أفضل موظف"
- زر/كارت داخل لوحة الموظف المؤهل.
- يعرض المرشحين المسموحين فقط.
- اختيار مرشح واحد فقط.
- بعد التصويت: إخفاء النموذج وإظهار رسالة نجاح + منع إعادة التصويت.
- بعد يوم 20: غلق التصويت تلقائيا.

2. واجهة Admin: "موظف الشهر"
- بلوك 1: نتائج التصويت
  - عدد المصوتين
  - ترتيب الموظفين بالأصوات
- بلوك 2: مؤشرات الأداء
  - الأعلى ساعات عمل
  - الأكثر حضورا مبكرا (حسب معيار متفق عليه)
  - الأعلى overtime
- بلوك 3: تقييم Admin
  - إدخال/تعديل score لكل موظف
- بلوك 4: ملخص نهائي للفائز (اختياري بحسب معادلة نهائية)

---

## 6) منطق الأعمال (Business Rules) المقترح تفصيليا

## 6.1 قواعد التصويت

- نافذة التصويت الشهرية: من بداية الشهر حتى 20 23:59:59.
- ابتداء من يوم 21: التصويت مغلق.
- مستخدم واحد = صوت واحد/شهر.
- لا تصويت للنفس.
- لا تصويت إذا المستخدم غير مؤهل (admin/manager/hr).
- لا تصويت لمرشح غير مؤهل (admin/manager/hr).

## 6.2 تعريف "الأكثر حضورا مبكرا" (يحتاج اتفاق)

المعيار المعتمد:
- "الأكثر حضورا مبكرا" = الموظف الأقل في إجمالي دقائق التأخير خلال الشهر.

قواعد كسر التعادل (Tie-breakers):
- 1) الأعلى في إجمالي ساعات العمل.
- 2) الأعلى في إجمالي Overtime.
- 3) الترتيب الأبجدي للاسم.

## 6.3 ساعات العمل في تقرير الحضور

- استبدال عمود "نسبة الحضور" بـ "عدد ساعات العمل".
- الحساب من مجموع work_minutes لكل موظف خلال الشهر.
- التنسيق في الجدول: HH:MM أو رقم ساعات عشري (حسب قرار العرض).

---

## 7) خطة التنفيذ العملية (Phased Plan)

## المرحلة 0: التحضير الفني
- إنشاء branch feature.
- تحديد strategy للترحيل role enum بأمان (خاصة في MySQL).
- تجهيز بيانات تجريبية accounts + employees.

## المرحلة 1: بنية البيانات والصلاحيات
- migrations للجداول/الأعمدة الجديدة.
- تحديث models والعلاقات:
  - User <-> Employee
  - User <-> UserProfile
- تحديث Middleware/helpers من isAdmin فقط إلى isAdminLike.
- تحديث FormRequest authorize affected.

## المرحلة 2: إدارة حسابات الموظفين (Admin)
- إضافة flows لإنشاء الحساب وربطه بالموظف.
- تطبيق rules:
  - job_title = admin => role = admin
  - job_title in [manager, hr] => role manager/hr مع صلاحيات admin-like
  - باقي الوظائف => role employee
- إرسال/عرض بيانات الدخول الأولية (بحذر أمني).

## المرحلة 3: شاشة حساب الموظف + أول دخول
- بناء صفحة حسابي داخل نفس الـ layout الحالي.
- إضافة upload avatar + bio + social links.
- تفعيل first-login password change guard.
- إضافة قسم الإحصائيات readonly باستخدام الخدمات الحالية.

## المرحلة 4: نظام التصويت الشهري
- APIs/Controllers للتصويت.
- validation صارم لقواعد الأهلية والموعد.
- شاشة الموظف للتصويت.
- شاشة admin لنتائج التصويت وعدد المصوتين.

## المرحلة 5: صفحة موظف الشهر + مؤشرات الأداء
- حساب top work hours من attendance_records.work_minutes.
- حساب top early attendance حسب التعريف النهائي.
- حساب top overtime من overtime_minutes.
- إدخال تقييم admin وعرضه.

## المرحلة 6: تعديل تقرير الحضور
- تحديث attendance report view لاستبدال نسبة الحضور بساعات العمل.
- تحديث أي service/query داعم لهذا العمود.
- مراجعة أي export إذا لزم نفس التغيير.

## المرحلة 7: الاختبارات وضمان الجودة
- Unit tests:
  - rules التصويت
  - eligibility checks
  - first-login guard
- Feature tests:
  - admin account creation
  - employee profile update
  - monthly vote flow end-to-end
- Authorization tests:
  - manager/hr يمتلكون صلاحيات admin-like.
  - employee ممنوع من شاشات الادمن.

---

## 8) الملفات المتوقع تعديلها (تقديري قبل التنفيذ)

- database/migrations/*
- app/Models/User.php
- app/Models/Employee.php
- app/Http/Middleware/CheckRole.php
- app/Http/Controllers/EmployeeController.php
- app/Http/Controllers/ProfileController.php أو Controller جديد للحساب
- app/Http/Controllers/AttendanceController.php (لتقرير الساعات)
- app/Http/Requests/* (authorize/rules)
- routes/web.php
- resources/views/layouts/sidebar.blade.php
- resources/views/attendance/report.blade.php
- resources/views/profile/* أو views جديدة للحساب
- ملفات views جديدة لموظف الشهر والتصويت

---

## 9) المخاطر المحتملة وكيف نتفاداها

- خطر خلط role مع job_title:
  - الحل: فصل واضح بينهما مع mapping rules.
- خطر كسر صلاحيات حالية تعتمد isAdmin:
  - الحل: introduce isAdminLike وتحديث شامل + tests.
- خطر تعديل enum role في بيئة production:
  - الحل: migration مدروس وقد يتطلب DBAL حسب نوع التعديل.
- خطر التلاعب بالتصويت عبر طلبات مباشرة:
  - الحل: validation في backend فقط، وعدم الاعتماد على UI.
- خطر رفع صور كبيرة/صيغة غير آمنة:
  - الحل: validation + resize + storage policies.

---

## 10) اقتراحات تطوير إضافية (اختيارية لكن مفيدة)

- إضافة Audit Log لكل العمليات الحساسة:
  - إنشاء حساب موظف
  - تغيير role/job_title
  - تسجيل تصويت
- إضافة إعداد قابل للتعديل من Admin:
  - تاريخ إغلاق التصويت (بدلا من تثبيته على يوم 21).
- إضافة سياسة قوة كلمة السر عند أول تغيير.
- إضافة إشعار داخل النظام للموظف إذا لم يصوت قبل الإغلاق.
- إضافة أرشفة شهرية لنتائج موظف الشهر لتقارير سنوية.

---

## 11) القرارات المعتمدة (بعد إجاباتك)

1. الوظيفة Job Title ستكون Enum قابلة للتوسعة لاحقا، مع عرض عربي في الواجهة.
2. البريد الإلكتروني غير قابل للتعديل، ويُنشأ تلقائيا بصيغة:
  - employee_slug@perfection.com
  - مثال: omar@perfection.com
3. كلمة السر الابتدائية ثابتة للجميع: 123456789، ثم تغيير إجباري عند أول تسجيل دخول.
4. لا يوجد إرسال إيميل تلقائي، ويكتفى بعرض بيانات الدخول داخل لوحة الادمن.
5. معيار "الأكثر حضورا مبكرا" = أقل إجمالي دقائق تأخير خلال الشهر.
6. تقييم Admin في موظف الشهر من 1 إلى 5.
7. سيتم اعتماد معادلة نهائية للنتيجة، مع قابلية إضافة تقييمات جديدة لاحقا.
8. Manager و HR لهم نفس واجهة الادمن بالكامل ونفس الصلاحيات.

---

## 12) ترتيب التنفيذ المقترح

- ابدأ ب Feature 1 أولا بالكامل (Accounts + Profile + Roles).
- ثم Feature 2 (Voting + Employee of Month).
- وأخيرا تعديل تقرير الحضور لاستبدال نسبة الحضور بساعات العمل.

السبب: فيتشر التصويت يعتمد على وجود ربط واضح بين User وEmployee وأدوار مستقرة.

---

## 13) خطة تنفيذ تفصيلية خطوة بخطوة (Execution Checklist)

## 13.1 Feature 1: حساب الموظف + الملف الشخصي + الصلاحيات

1. Migration: توسيع صلاحيات users
- تعديل enum role ليشمل: admin, manager, hr, employee.
- إضافة employee_id + must_change_password + last_password_changed_at.

2. Migration: إضافة الوظيفة في employees
- إضافة job_title كـ Enum داخلي قابل للتوسعة.
- القيم الأولية:
  - designer
  - three_d
  - customer_service
  - developer
  - hr
  - admin
  - manager

3. Migration: إنشاء user_profiles
- user_id unique FK.
- avatar_path, bio, social_link_1, social_link_2.

4. Models + Relations
- User:
  - belongsTo Employee.
  - hasOne UserProfile.
  - isAdminLike() => role in [admin, manager, hr].
- Employee:
  - hasOne User.

5. قواعد إنشاء الحساب من لوحة الادمن
- عند اختيار وظيفة الموظف:
  - admin => role=admin
  - manager => role=manager
  - hr => role=hr
  - أي وظيفة أخرى => role=employee
- email يتم توليده تلقائيا:
  - slug(name) + "@perfection.com"
  - مع ضمان uniqueness عند التكرار (مثال: omar2@perfection.com)
- password الابتدائية:
  - 123456789 (hashed)
- must_change_password=true.

6. فرض تغيير كلمة السر عند أول تسجيل دخول
- Middleware جديد: ForcePasswordChange.
- أي مستخدم must_change_password=true يتم تحويله لشاشة تغيير كلمة السر فقط.
- بعد التغيير الناجح:
  - must_change_password=false
  - last_password_changed_at=now()

7. شاشة "حسابي" للموظف
- تبويب البيانات الأساسية:
  - الاسم readonly
  - الوظيفة readonly
  - البريد readonly
  - صورة + Bio + رابطين تواصل قابلين للتعديل
- تبويب الأمان:
  - تغيير كلمة السر
- تبويب الإحصائيات (readonly):
  - أيام الحضور
  - ساعات التأخير
  - ساعات overtime
  - جدول الشهر الحالي

8. توحيد الواجهة والصلاحيات
- إظهار نفس واجهة الادمن بالكامل لـ admin/manager/hr.
- تحديث شروط الواجهة من isAdmin إلى isAdminLike حيث يلزم.
- تحديث authorize في FormRequests.

9. اختبارات Feature 1
- إنشاء حساب موظف تلقائيا.
- التحقق من email generation و uniqueness.
- منع تعديل البريد من الموظف.
- إجبار تغيير كلمة السر في أول دخول.
- صلاحيات manager/hr مساوية للادمن.

## 13.2 Feature 2: التصويت + موظف الشهر

1. Migration: employee_month_votes
- قيود التصويت مرة واحدة شهريا عبر unique(voter_user_id, vote_month, vote_year).

2. Migration: employee_month_admin_scores
- score من 1 إلى 5 (validation + check).
- unique(employee_id, month, year).

3. بناء منطق الأهلية للتصويت
- المصوّت المؤهل: role=employee فقط.
- المرشح المؤهل: role=employee فقط.
- منع التصويت للنفس.
- منع التصويت بعد يوم 20.

4. واجهة الموظف للتصويت
- قائمة مرشحين بدون نفسه وبدون admin/manager/hr.
- اختيار مرشح واحد فقط.
- بعد الحفظ: قفل دائم للشهر الحالي + رسالة نجاح.

5. واجهة الادمن "موظف الشهر"
- جدول نتائج التصويت (عدد الأصوات + الترتيب).
- مؤشر أعلى ساعات عمل (sum(work_minutes)).
- مؤشر الأكثر حضورا مبكرا (أقل late_minutes).
- مؤشر أعلى overtime (sum(overtime_minutes)).
- إدخال تقييم admin (1..5).

6. المعادلة النهائية (v1) لاختيار موظف الشهر
- Score_Final = 0.40 * VoteScore + 0.25 * AdminScore + 0.20 * WorkHoursScore + 0.10 * PunctualityScore + 0.05 * OvertimeScore

تعريف كل Score (بعد التطبيع إلى 100):
- VoteScore: نسبة أصوات الموظف من إجمالي الأصوات الصحيحة.
- AdminScore: (admin_score / 5) * 100.
- WorkHoursScore: employee_work_hours / max_work_hours * 100.
- PunctualityScore: (1 - employee_late_minutes / max_late_minutes) * 100.
  - إذا max_late_minutes = 0 => كل الموظفين 100.
- OvertimeScore: employee_ot_hours / max_ot_hours * 100.
  - إذا max_ot_hours = 0 => كل الموظفين 0.

ملاحظة توسعة:
- نحفظ أوزان المعادلة في settings لتسهيل إضافة تقييمات جديدة لاحقا بدون تعديل كود كبير.

7. تعديل تقرير الحضور
- في تقرير الحضور الشهري:
  - إزالة عمود "نسبة الحضور".
  - إضافة عمود "عدد ساعات العمل" من مجموع work_minutes.
  - تنسيق العرض HH:MM.

8. اختبارات Feature 2
- لا يمكن التصويت مرتين بنفس الشهر.
- لا يمكن التصويت للنفس.
- لا يمكن التصويت بعد يوم 20.
- لا يظهر admin/manager/hr كمرشحين.
- المعادلة النهائية تعطي ترتيبا صحيحا بناء على البيانات.

## 13.3 ترتيب تسليم مقترح (Milestones)

- Milestone A: DB + Roles + First Login + Profile.
- Milestone B: Voting Engine + Admin Scores + Employee of Month Page.
- Milestone C: Attendance Report hours column + QA + UAT fixes.
