# تحليل شامل لميزة الأقسام + طلبات الإجازة

## 1) ملخص ما فهمته من المطلوب

### A. Feature 1: نظام الأقسام والصلاحيات
- إضافة كيان جديد: القسم (Department).
- كل موظف يجب أن يكون له:
  - وظيفة (Job Title)
  - قسم (Department)
- كل قسم له:
  - مدير قسم واحد
  - موظفون تابعون له
- HR و Admin:
  - لهم صلاحية كاملة على النظام (كما هو الآن)
  - لا يتم تقييدهم بقسم
  - هم فقط من ينشئون الأقسام ويعدلونها
  - هم من يحددون مدير القسم
  - هم من يضيفون الموظفين للأقسام
  - HR يستطيع إضافة وظائف جديدة
- في واجهات الموظفين:
  - يظهر تحت اسم الموظف: الوظيفة (القسم)
  - لو الموظف مدير قسم: يظهر "مدير قسم {اسم القسم}"

### B. Feature 2: صلاحيات مدير القسم
- مدير القسم يرى فقط الموظفين التابعين لقسمه.
- يرى إحصائياتهم (حضور/غياب/تأخير/أداء يومي/مهام).
- يستطيع تعيين مهام لموظفي قسمه فقط.
- يستطيع اختيار أفضل موظف من قسمه (وفق القيود المتفق عليها).
- لا يستطيع:
  - تعديل بيانات الموظف الأساسية
  - رفع ملف Excel
  - إدارة إعدادات الحضور أونلاين
  - تنفيذ صلاحيات HR/Admin الشاملة

### C. Feature 3: تعديل منطق موظف الشهر
- مديرو الأقسام لا يدخلون في منافسة أفضل موظف.
- مديرو الأقسام لا يظهرون في قائمة المرشحين للتصويت.
- في صفحة موظف الشهر:
  - تظل المراكز الثلاثة الأولى للموظفين فقط
  - إضافة قسم مستقل: "مدير الشهر"
- قاعدة مدير الشهر:
  - يفوز مدير القسم الذي حصل 3 موظفين من قسمه على جائزة أفضل موظف في الشهر
  - (هذه القاعدة تحتاج توضيح هل المقصود 3 موظفين ضمن نفس الشهر أم تراكم تاريخي)

### D. Feature 4: طلبات الإجازة
- إضافة عنصر في الـ Navbar للموظف: طلب إجازة.
- شاشة طلب الإجازة للموظف:
  - اختيار التاريخ من Calendar
  - اختيار عدد الأيام
  - كتابة السبب
  - إرسال الطلب => الحالة Pending
- عند الإرسال:
  - يصل الطلب إلى HR + مدير قسم الموظف
  - لو الموظف بدون مدير قسم: يصل إلى HR فقط
- شاشة إدارة الطلبات (HR + مدير القسم):
  - جدول طلبات
  - صفحة تفاصيل لكل طلب
  - حالة كل طرف مستقلة: HR (موافق/رافض/معلق) + Manager (موافق/رافض/معلق)
  - اعتماد نهائي:
    - لو يوجد مدير قسم: يجب موافقة HR ومدير القسم معاً
    - لو لا يوجد مدير: موافقة HR تكفي
  - عند الموافقة/الرفض: ملاحظات اختيارية لكل طرف
  - HR يستطيع الموافقة الجزئية على عدد أيام أقل من المطلوب

### E. Feature 5: أهلية طلب الإجازة (Eligibility)
- لكل موظف:
  - تاريخ بداية العمل
  - عدد أيام مطلوبة قبل أن يحق له طلب إجازة (مثلاً 120 يوم)
  - عدد أيام الإجازة السنوية
- المطلوب إظهاره:
  - عدد الأيام المتبقية حتى يسمح له بطلب الإجازة
  - عدد أيام الإجازة السنوية
  - عدد الأيام المتبقية من رصيد الإجازات
- لو الموظف غير مؤهل:
  - تظهر شاشة منع واضحة: لا يمكنك طلب إجازة الآن، متبقي {X} يوم
- لو مؤهل:
  - يظهر عداد الرصيد
  - يظهر جدول بالإجازات التي أخذها
- قيم افتراضية لهذه الإعدادات من صفحة الإعدادات (HR/Admin)

---

## 2) تحليل المشروع الحالي (Current State)

## البنية الحالية المراجعة
- النظام Laravel + Blade + Services.
- الأدوار الحالية في النظام تعتمد على users.role عبر middleware role.
- الأدوار الحالية المستخدمة عملياً: admin, manager, hr, employee, user, office_girl.
- لا يوجد حالياً كيان Department في قاعدة البيانات.
- لا يوجد حالياً نظام Leave Requests أو جداول خاصة بالإجازات السنوية.

## نقاط مهمة موجودة حالياً وتؤثر على التنفيذ
1. role manager حالياً يعتبر Admin-like في عدة أماكن.
2. صفحات كثيرة حالياً تفتح لـ admin/manager/hr معاً.
3. الموظف له job_title من Enum ثابت في الكود (ليس dynamic من DB).
4. التصويت ومرشحو موظف الشهر يعتمدون على role = employee.
5. المهام اليومية والمراجعات حالياً غير مقيدة بقسم، بل غالباً تشمل كل الموظفين.

## ملفات محورية تم فحصها
- routes/web.php
- app/Models/User.php
- app/Models/Employee.php
- app/Http/Middleware/CheckRole.php
- app/Services/Employee/EmployeeAccountService.php
- app/Services/Employee/EmployeeService.php
- app/Services/EmployeeOfMonth/VoteEligibilityService.php
- app/Services/EmployeeOfMonth/EmployeeOfMonthMetricsService.php
- app/Http/Controllers/TaskAdminController.php
- app/Services/DailyPerformance/DailyPerformanceReviewService.php
- app/Services/Dashboard/DashboardStatisticsService.php
- resources/views/layouts/sidebar.blade.php
- resources/views/employees/*.blade.php
- resources/views/employee-of-month/*.blade.php
- resources/views/settings/index.blade.php

---

## 3) الفجوات بين المطلوب والحالي

1. لا يوجد Department model/table/relations.
2. لا يوجد ربط بين employee وdepartment ولا مدير قسم رسمي.
3. role manager الحالي عام، وليس مرتبطاً بقسم محدد.
4. صلاحيات manager واسعة حالياً (قريبة من Admin-like) ويجب تقليلها.
5. Job titles حالياً Enum ثابت؛ المطلوب إضافة وظائف جديدة بواسطة HR (dynamic).
6. لا يوجد أي Leave workflow أو approvals ثنائية أو partial approval.
7. لا توجد إعدادات Leave defaults ولا رصيد إجازات سنوي.
8. لا توجد شاشة "مدير الشهر" أو منطق احتسابها.

---

## 4) التصميم المقترح (Data Model + Business Rules)

## A. نموذج البيانات للأقسام

### جداول جديدة
1. departments
- id
- name (unique)
- code (optional unique)
- manager_employee_id (nullable, FK employees.id)
- is_active
- timestamps

2. department_employee_history (اختياري لكن موصى به)
- id
- employee_id
- department_id
- from_date
- to_date nullable
- assigned_by
- timestamps

### تعديلات على employees
- إضافة department_id nullable FK
- إضافة is_department_manager boolean default false

> ملاحظة: يمكن الاكتفاء بـ departments.manager_employee_id بدون is_department_manager، لكن وجود flag يساعد في الاستعلامات والعرض.

## B. نموذج الوظائف (بديل Enum)

### الخيار المفضل
- إنشاء جدول job_titles:
  - id, key, name_ar, system_role_mapping(nullable), is_system, is_active, timestamps
- تحويل employees.job_title من enum-cast إلى FK job_title_id.

### خيار انتقالي أقل كسر
- الإبقاء المؤقت على enum الحالي.
- إضافة جدول custom_job_titles للوظائف الجديدة الخاصة بـ HR.
- لاحقاً تنفيذ migration انتقال كاملة إلى model ديناميكي واحد.

> التوصية: تنفيذ التحويل الكامل إلى job_titles لتجنب تعقيد مزدوج.

## C. نموذج طلبات الإجازة

### جداول جديدة
1. leave_requests
- id
- employee_id
- department_id nullable
- manager_employee_id nullable
- start_date
- end_date
- requested_days
- reason
- status (pending, partially_approved, approved, rejected, cancelled)
- hr_status (pending, approved, partially_approved, rejected)
- manager_status (pending, approved, rejected, not_required)
- hr_approved_days nullable
- final_approved_days nullable
- submitted_at
- finalized_at nullable
- timestamps

2. leave_request_approvals (audit trail)
- id
- leave_request_id
- actor_user_id
- actor_role (hr|department_manager)
- decision (approved|partially_approved|rejected)
- approved_days nullable
- note nullable
- decided_at
- timestamps

3. leave_balances
- id
- employee_id unique
- annual_quota_days
- used_days
- remaining_days (مشتق أو مخزن)
- year
- timestamps

4. leave_policies (أو ضمن settings)
- default_required_work_days_before_leave
- default_annual_leave_days
- allow_partial_hr_approval (bool)
- ...

5. employee_leave_profiles
- employee_id unique
- employment_start_date nullable
- required_work_days_before_leave nullable
- annual_leave_quota nullable
- timestamps

---

## 5) قواعد العمل (Business Logic)

## A. قواعد الأقسام
1. الموظف العادي يجب أن يكون له قسم (إلا استثناءات متفق عليها).
2. HR/Admin بدون قسم ولا يخضعان لمدير قسم.
3. مدير القسم يجب أن يكون موظفاً داخل نفس القسم.
4. مدير القسم لا يكتسب صلاحيات HR/Admin.

## B. قواعد صلاحيات مدير القسم
1. scope البيانات = موظفي قسمه فقط.
2. يحق له:
- عرض attendance reports لموظفي قسمه
- عرض daily performance لموظفي قسمه
- إنشاء/إسناد مهام لموظفي قسمه
- تقييم/متابعة أداء فريقه
3. لا يحق له:
- import/excel
- settings الحساسة
- تعديل بيانات الموظفين الأساسية
- تغيير صلاحيات/أدوار المستخدمين
- remote attendance policy controls

## C. قواعد موظف الشهر
1. المرشحون = موظفون role=employee وغير مديري أقسام.
2. مديرو الأقسام مستبعدون من الترشيح والتصويت كمرشحين.
3. إضافة leader board منفصل: مدير الشهر.
4. قاعدة مدير الشهر (نسخة مقترحة قابلة للتنفيذ):
- لكل مدير: احسب عدد الموظفين من قسمه الذين فازوا ضمن Top 3 في نفس الشهر.
- إذا العدد >= 3 => يستحق مدير الشهر.
- عند التعادل: الأعلى في متوسط نقاط موظفي القسم ثم أقل Late total.

## D. قواعد طلب الإجازة
1. Eligibility:
- لا يمكن التقديم إذا employment_start_date غير محدد.
- لا يمكن التقديم إذا أيام الخدمة الفعلية < required_work_days_before_leave.
2. الرصيد:
- لا يمكن اعتماد أيام تتجاوز remaining_days.
3. مسار الموافقة:
- بدون مدير قسم: HR فقط يحسم الطلب.
- مع مدير قسم: يلزم موافقة الطرفين.
4. الموافقة الجزئية:
- HR يستطيع تقليل الأيام المعتمدة.
- final_approved_days = min(hr_approved_days, manager_approved_days_or_requested_days).
5. الرفض من أي طرف يؤدي لرفض نهائي (قابل للتعديل حسب السياسة).

---

## 6) Workflow تفصيلي

## A. Workflow الأقسام
1. HR/Admin ينشئ قسم جديد.
2. HR/Admin يختار مدير القسم.
3. HR/Admin يربط الموظفين بالقسم.
4. النظام يحدّث صلاحيات العرض تلقائياً بناءً على القسم.

## B. Workflow مدير القسم
1. يدخل Dashboard القسم.
2. يرى مؤشرات فريقه فقط.
3. يفتح الحضور/الأداء/المهام لفريقه فقط.
4. يرشح أفضل موظف من القسم (إن كانت السياسة تسمح).

## C. Workflow طلب الإجازة
1. الموظف يفتح صفحة طلب إجازة.
2. النظام يتحقق Eligibility.
3. إذا غير مؤهل: رسالة منع + الأيام المتبقية.
4. إذا مؤهل: يملأ الطلب ويرسله.
5. الطلب يظهر في صندوق HR ومدير القسم (أو HR فقط).
6. كل طرف يتخذ قراره + ملاحظة.
7. عند اكتمال شروط القبول يصبح Approved/Partially Approved.
8. يتحدث رصيد الإجازات ويظهر للموظف.

---

## 7) خطة تنفيذ مرحلية (Implementation Plan)

## Phase 0: تثبيت القرارات
- حسم الأسئلة المفتوحة (مذكورة آخر المستند).
- اعتماد ERD النهائي.

## Phase 1: قاعدة البيانات
- migrations: departments + employee_leave_profiles + leave_requests + leave_request_approvals + leave_balances (+ job_titles إن اعتمد التحويل).
- backfill scripts للبيانات الحالية.
- indexes على employee_id/department_id/status/dates.

## Phase 2: النماذج والعلاقات
- إضافة Models وعلاقات Eloquent.
- تحديث Employee/User relations.
- إضافة scopes:
  - scopeDepartmentVisibleTo(User $user)
  - scopeManagedBy(User $manager)

## Phase 3: الصلاحيات
- refactor middleware والسياسات إلى Permission Matrix واضحة.
- تقليل صلاحيات role=manager الحالية.
- إضافة طبقة authorization لكل Controller متأثر.

## Phase 4: واجهات الأقسام والموظفين
- CRUD للأقسام (HR/Admin).
- تعديل create/edit الموظف لاختيار القسم.
- تحديث employee list/profile لعرض "الوظيفة (القسم)".
- إضافة شارات "مدير قسم ...".

## Phase 5: مهام/أداء/حضور scoped by department
- Task admin pages: فلترة على موظفي القسم للمدير.
- Daily performance review: scope قسم المدير.
- Attendance reports/dashboard cards: scope قسم المدير.

## Phase 6: موظف الشهر + مدير الشهر
- استبعاد مديري الأقسام من candidate pool.
- إضافة احتساب مدير الشهر.
- تحديث UI التصويت وصفحة الإدارة والنتائج.

## Phase 7: طلبات الإجازة
- صفحات الموظف:
  - request form
  - leave balance/history
  - eligibility blocker
- صفحات HR/Manager:
  - inbox table
  - request details
  - actions approve/reject/partial + notes
- notification events (in-app/email optional).

## Phase 8: الإعدادات
- إضافة Leave defaults داخل settings.
- شاشة HR لإدارة start date / required days / quota لكل موظف.

## Phase 9: الاختبارات
- Unit tests ل business rules.
- Feature tests للصلاحيات والمسارات.
- Tests لحالات edge (partial approval, dual approval, no manager).

## Phase 10: الإطلاق
- migration run + data integrity checks.
- rollout تدريجي + monitoring + rollback plan.

---

## 8) مصفوفة الصلاحيات المقترحة (مختصر)

- Admin
  - كامل الصلاحيات.
- HR
  - كامل الصلاحيات التشغيلية + الأقسام + الوظائف + الإجازات.
- Department Manager
  - View/Task/Review ضمن قسمه فقط.
  - Approve leave (ضمن قسمه فقط).
  - بدون صلاحيات import/settings/employee-edit الحساسة.
- Employee
  - بياناته + طلب إجازة + تصويت + مهامه + أداؤه.
- Evaluator(User)
  - كما هو حالياً (بحسب احتياج المشروع).

---

## 9) المخاطر الفنية المتوقعة

1. تغيير role manager الحالي قد يكسر شاشات تعتمد manager=admin-like.
2. التحول من JobTitle Enum إلى dynamic job titles يحتاج migration دقيقة.
3. منطق الموافقة الثنائية + الموافقة الجزئية يحتاج state machine واضحة.
4. التعامل مع رصيد الإجازات السنوي يحتاج سياسات reset سنوي واضحة.
5. performance: كثرة joins في dashboards بعد إضافة scope department.

---

## 10) أسئلة مفتوحة قبل التنفيذ (مهم)

1. هل نحتفظ بدور manager الحالي كـ Department Manager أم نضيف role جديد مثل department_manager؟
2. هل يمكن للموظف أن ينتمي لأكثر من قسم أم قسم واحد فقط؟
3. هل مدير القسم يجب أن يكون role=manager فقط أم يمكن role=employee مع flag مدير قسم؟
4. قاعدة "مدير الشهر":
- هل "3 موظفين فازوا" في نفس الشهر؟
- أم 3 فوزات تراكمية عبر أشهر؟
5. هل مدير القسم يشارك كناخب في موظف الشهر أم يتم استبعاده تماماً من عملية التصويت؟
6. في الموافقة الجزئية:
- هل مدير القسم أيضاً يمكنه approval جزئي أم HR فقط؟
7. عند رفض أحد الطرفين في طلب الإجازة، هل يغلق الطلب نهائياً أم يمكن إعادة تقديم/تعديل؟
8. هل الإجازات تُحسب أيام تقويمية أم أيام عمل فقط (باستثناء الجمعة/العطلات الرسمية)؟
9. هل يوجد أنواع إجازات متعددة (سنوية/مرضية/طارئة) أم نوع واحد الآن؟
10. عند عدم وجود Department Manager، هل أي Manager يمكنه الموافقة أم HR فقط؟

---

## 11) اقتراحات تحسين

1. اعتماد Permissions/Policies بدلاً من الاعتماد الكامل على role strings داخل الكنترولرز.
2. بناء Audit Log موحد لكل القرارات الحساسة (تعديل قسم، تعيين مدير، اعتماد إجازة).
3. إضافة Notifications (داخل النظام + Email).
4. إضافة Dashboard خاص لمدير القسم مع KPIs الفريق.
5. توحيد نظام الوظائف إلى جدول ديناميكي لتجنب تعديل الكود كل مرة.
6. إضافة API layer مستقبلية لتسهيل تطبيق موبايل.

---

## 12) خطة تنفيذ عملية مقترحة (Sprint-wise)

### Sprint 1
- Departments DB + CRUD + employee linkage + manager scoping الأساسي.

### Sprint 2
- صلاحيات مدير القسم في attendance/tasks/daily-performance + UI updates.

### Sprint 3
- Employee of Month updates + مدير الشهر.

### Sprint 4
- Leave Requests end-to-end + approvals + balances + settings.

### Sprint 5
- Test hardening + bug fixing + launch.

---

## 13) جاهزية البدء
- التحليل مكتمل مبدئياً.
- قبل الكود: مطلوب تأكيد الأسئلة المفتوحة في القسم 10 لتثبيت الـ architecture ومنع إعادة العمل.
