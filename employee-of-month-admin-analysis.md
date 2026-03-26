# تحليل صفحة لوحة موظف الشهر (Admin)

## الهدف من الصفحة
صفحة الأدمن الخاصة بموظف الشهر تعرض:
- ترتيب الموظفين النهائي حسب المعادلة.
- تفصيل درجات كل عنصر (مهام، تصويت، ساعات عمل، انضباط).
- مؤشرات مساعدة (إجمالي الأصوات، نسبة تقييم المهام، أفضل ساعات عمل).
- تصدير النتائج إلى Excel.
- اعتماد النتائج وحفظها في History.

الملف الرئيسي للواجهة:
- `resources/views/employee-of-month/admin.blade.php`

## مسار التشغيل (High-Level Flow)
1. الأدمن يفتح الصفحة `/employee-of-month/admin` مع `month` و `year`.
2. `EmployeeOfMonthAdminController@index` يستدعي:
	 - `EmployeeOfMonthMetricsService@getMonthlyMetrics`
	 - `EmployeeOfMonthScoringService@calculateForMonth`
3. يتم عرض الترتيب النهائي + الجداول التفصيلية.
4. عند الضغط على "اعتماد النتائج":
	 - `EmployeeOfMonthFinalizationService@finalizeMonth`
	 - حفظ النتائج في `employee_of_month_results` (upsert).
5. عند الضغط على "تصدير Excel":
	 - نفس الحساب يتنفذ ثم يتم تنزيل ملف الترتيب.

الملفات الأساسية:
- `app/Http/Controllers/EmployeeOfMonthAdminController.php`
- `app/Services/EmployeeOfMonth/EmployeeOfMonthMetricsService.php`
- `app/Services/EmployeeOfMonth/EmployeeOfMonthScoringService.php`
- `app/Services/EmployeeOfMonth/EmployeeOfMonthFinalizationService.php`

## دورة الراتب والزمن المستخدم في الحساب
المشروع لا يعتمد على الشهر الميلادي التقليدي فقط، بل على **Payroll Period**:
- بداية الدورة: يوم 22 من الشهر السابق.
- نهاية الدورة: يوم 21 من الشهر الحالي.

المرجع:
- `app/Services/Payroll/PayrollPeriod.php`

بالتالي عند حساب شهر مثل مارس 2026:
- فترة البيانات = من 22 فبراير 2026 إلى 21 مارس 2026.

## تجميع البيانات (Metrics Layer)
من خلال `EmployeeOfMonthMetricsService` يتم بناء صف لكل موظف مؤهل:

### 1) الموظفون المؤهلون
- نشط (`is_active = true`)
- مرتبط بمستخدم role = `employee`

### 2) التصويت
- `votes_count`: عدد الأصوات التي حصل عليها الموظف في الشهر المحدد.
- `total_valid_votes`: إجمالي الأصوات الصحيحة من موظفين role = `employee`.
- `voters_count`: عدد الموظفين الذين قاموا بالتصويت (distinct voters).

الجداول:
- `employee_month_votes`

### 3) الحضور والانصراف
يتم تجميع:
- `work_minutes`
- `late_minutes`
- `overtime_minutes`

من جدول:
- `attendance_records`

### 4) المهام
من خلال `EmployeeTaskScoreService@getMonthlyTaskMetrics`:
- لكل موظف:
	- `assigned_tasks_count`
	- `evaluated_tasks_count`
	- `task_score_raw` = متوسط درجات تقييم المهام المقيمة فقط.
- على مستوى الفترة:
	- `tasks_count`
	- `evaluated_tasks_count`
	- `coverage_ratio` = نسبة المهام المقيمة من إجمالي المهام.

مهم:
- كل مهمة لها تقييم واحد كحد أقصى (unique على `task_id` في `employee_month_task_evaluations`).
- المهام غير المقيمة لا تدخل في حساب `task_score_raw`.

### 5) تقييم الإدارة
يتم تحميل `admin_score` من جدول `employee_month_admin_scores`، لكن في النسخة الحالية من المعادلة وزنه الافتراضي = 0 (غير مؤثر فعليًا على النتيجة النهائية).

## طريقة الحساب (Scoring Layer)
الملف: `app/Services/EmployeeOfMonth/EmployeeOfMonthScoringService.php`

### الأوزان الافتراضية الحالية
- Tasks = 40%
- Punctuality = 15%
- Work Hours = 20%
- Vote = 25%

يمكن تعديل الأوزان من الإعدادات (`SettingService`) عبر مفاتيح:
- `employee_of_month.weights.tasks`
- `employee_of_month.weights.punctuality`
- `employee_of_month.weights.work_hours`
- `employee_of_month.weights.vote`

### التطبيع (Normalization)
القيم الخام لا تُجمع مباشرة، بل يتم تحويل كل معيار إلى درجة من 20 إلى 100 باستخدام Percentiles:
- حساب $P10$ و $P90$ للقيم.
- قص أي قيمة خارج النطاق إلى حدود $P10..P90$.
- تحويل القيمة إلى مقياس نسبي ثم إلى درجة بين 20 و 100.

القواعد المهمة:
- إذا كل القيم متساوية (range شبه صفر): جميع الموظفين يأخذون درجة حيادية = 60.
- إذا قيمة الموظف `null`: أيضًا درجة حيادية = 60.
- للمعايير التي "الأعلى أفضل" (Tasks, Vote, Work Hours): الزيادة ترفع الدرجة.
- للانضباط `late_minutes` (الأقل أفضل): المعادلة تنعكس بحيث التأخير الأقل يعطي درجة أعلى.

### المعادلة النهائية
بعد التطبيع:

$$
Final = 0.40 \cdot TaskScore + 0.15 \cdot PunctualityScore + 0.20 \cdot WorkHoursScore + 0.25 \cdot VoteScore
$$

- يتم تقريب النتيجة إلى منزلتين عشريتين.
- الترتيب النهائي يكون تنازليًا حسب `final_score`.

### المعايير المفعلة (Criteria Toggle)
يوجد دعم لاختيار معايير مفعلة من الإعدادات عبر `employee_of_month.criteria` (JSON).
- إذا لم توجد إعدادات صالحة: يستخدم النظام كل المعايير الافتراضية.

## اعتماد النتائج وتخزين الـ History
عند الضغط على "اعتماد النتائج":
- يتم إعادة الحساب لنفس الشهر/السنة.
- يتم تجهيز payload لكل موظف يتضمن:
	- `final_score`
	- `breakdown` (JSON)
	- `formula_version`
	- `generated_at`
- حفظ عبر `upsert` في جدول `employee_of_month_results` على المفتاح الفريد:
	- (`employee_id`, `month`, `year`)

هذا يعني:
- الضغط المتكرر على اعتماد النتائج لنفس الشهر يقوم بتحديث نفس السجلات بدل تكرارها.

## الجدولة التلقائية (Auto Finalization)
يوجد أمر كونسول:
- `employee-of-month:auto-finalize`

ومجدول في:
- `app/Console/Kernel.php`

التوقيت:
- شهريًا يوم 21 الساعة 23:45.

الهدف:
- اعتماد نتائج دورة الرواتب تلقائيًا عند نهاية نافذة الشهر.

## Workflow التصويت (من جانب الموظف)
الملفات:
- `app/Http/Controllers/EmployeeOfMonthVoteController.php`
- `app/Services/EmployeeOfMonth/VoteEligibilityService.php`
- `app/Services/EmployeeOfMonth/VoteSubmissionService.php`

الخطوات:
1. الموظف يدخل صفحة التصويت.
2. النظام يعرض المرشحين المؤهلين (موظفين نشطين فقط، بدون نفس الموظف).
3. قبل الحفظ يتم التحقق من:
	 - المستخدم مؤهل للتصويت.
	 - نافذة التصويت مفتوحة (داخل دورة 22→21).
	 - لم يصوت سابقًا في نفس الشهر.
	 - لا يصوت لنفسه.
4. يتم الحفظ داخل Transaction مع قفل (`lockForUpdate`) لتقليل race condition.
5. يوجد Unique Index يمنع التصويت المكرر على مستوى قاعدة البيانات.

## ملاحظات مهمة أثناء القراءة
- `admin_score` موجود في البيانات لكنه غير داخل المعادلة الحالية (وزنه 0).
- `overtime` كذلك موجود legacy ووزنه 0.
- عرض "Coverage المهام" في الصفحة هو مستوى تغطية تقييم المهام للفترة وليس درجة موظف فردي.
- عند عدم كفاية البيانات، النظام يميل للدرجة الحيادية 60 بدل إسقاط الموظف من الحساب.

## Routes مرتبطة بالصفحة
- `GET /employee-of-month/admin` → عرض اللوحة.
- `GET /employee-of-month/admin/export` → تصدير الترتيب.
- `POST /employee-of-month/admin/finalize` → اعتماد النتائج.
- `POST /employee-of-month/admin/score` → حفظ تقييم الإدارة (غير مؤثر حاليًا ما لم تُفعّل أوزانه).

المرجع:
- `routes/web.php`

