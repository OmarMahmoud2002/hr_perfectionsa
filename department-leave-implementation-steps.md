# خطة التنفيذ الكاملة - Departments + Department Manager + Employee of Month + Leave Requests

## 1) الهدف
تنفيذ المزايا المطلوبة بالكامل داخل النظام الحالي مع الحفاظ على استقرار الصلاحيات القائمة لـ Admin/HR/Manager، وإضافة صلاحيات مقيّدة لمدير القسم.

---

## 2) النتيجة النهائية المطلوبة

1. كل موظف له وظيفة + قسم (قسم واحد فقط).
2. Role جديدة: department_manager.
3. مدير القسم يرى ويتعامل فقط مع موظفي قسمه.
4. تقييم الأداء اليومي متاح لمدير القسم على فريقه فقط.
5. أفضل موظف:
- 4 فائزين بدل 3.
- الحد الأدنى 90 نقطة بدل 85.
- مدير القسم يصوّت لأعضاء قسمه فقط.
- مدير القسم لا يظهر كمرشح.
6. أفضل مدير:
- فائز واحد فقط.
- الشرط: 3 من 4 أوائل من نفس قسمه في نفس الشهر.
7. إيقاف إعلان النتيجة قبل الاعتماد الرسمي.
8. طلبات الإجازة كاملة بمنطق HR + مدير القسم (أو HR فقط عند عدم وجود مدير قسم).
9. HR فقط يستطيع الموافقة الجزئية على عدد الأيام.
10. عند رفض الطلب يغلق، والموظف يرسل طلبا جديدا.

---

## 3) مراحل التنفيذ العملية

## Phase 0 - تهيئة وفرع التطوير

1. إنشاء فرع جديد للتنفيذ.
2. مراجعة آخر migrations للتأكد من عدم تعارض timestamps.
3. تجهيز قائمة اختبار يدوية سريعة قبل بدء التعديلات (baseline).

مخرجات المرحلة:
- فرع نظيف + baseline واضح.

---

## Phase 1 - قاعدة البيانات (Database)

## 1.1 تعديل users.role

1. إضافة department_manager إلى enum role في users.
2. التأكد من down migration يدعم الرجوع الآمن.

## 1.2 جداول الأقسام

1. إنشاء migration لجدول departments:
- id, name, code, manager_employee_id, is_active, timestamps.

2. تعديل employees:
- إضافة department_id (FK -> departments.id).
- إضافة is_department_manager (boolean default false).

3. (اختياري موصى به) إنشاء department_employee_history.

## 1.3 الوظائف الديناميكية

1. إنشاء جدول job_titles.
2. إضافة job_title_id إلى employees.
3. عمل migration نقل بيانات من job_title النصي الحالي إلى job_titles.
4. الإبقاء المؤقت على العمود القديم لحين اكتمال refactor ثم حذفه في migration لاحقة.

## 1.4 جداول الإجازات

1. employee_leave_profiles.
2. leave_requests.
3. leave_request_approvals.
4. leave_balances.
5. إضافة مفاتيح إعدادات افتراضية للإجازات في settings.

مخرجات المرحلة:
- schema مكتمل للمزايا الجديدة.

---

## Phase 2 - Models + Relations + Casts

1. إنشاء/تعديل Models:
- Department
- JobTitle
- LeaveRequest
- LeaveRequestApproval
- LeaveBalance
- EmployeeLeaveProfile

2. تحديث العلاقات:
- Employee belongsTo Department.
- Department hasMany Employees.
- Department belongsTo ManagerEmployee.
- Employee belongsTo JobTitle.
- Employee hasOne LeaveProfile.
- Employee hasMany LeaveRequests.

3. تحديث User helpers:
- isDepartmentManager()
- تحديث isAdminLike بدون كسر منطق manager الحالي.

4. تحديث fillable/casts في Employee/User.

مخرجات المرحلة:
- علاقة البيانات شغالة بالكامل على مستوى Eloquent.

---

## Phase 3 - Services وBusiness Logic

## 3.1 إدارة الأقسام

1. DepartmentService:
- create/update/delete
- assignManager
- assignEmployees

2. Rule مهم:
- عند تعيين manager_employee_id:
  - يتم ترقية User.role = department_manager للشخص المختار (إن لم يكن admin/hr/manager).
  - يتم تحديث is_department_manager = true على الموظف.

3. عند إلغاء مدير القسم:
- إعادة role للموظف إلى employee (إذا كان department_manager فقط).
- is_department_manager = false.

## 3.2 منطق الرؤية حسب القسم

1. DepartmentScopeService:
- getVisibleEmployeesFor(User $actor)
- إذا department_manager => موظفو قسمه فقط.
- إذا admin/hr/manager => كل الموظفين.

2. تطبيق scope نفسه على:
- daily performance review
- tasks admin
- attendance reports
- dashboard stats

## 3.3 منطق موظف الشهر

1. تحديث constants:
- winners_count = 4
- min_ranking_score = 90

2. المرشحون:
- active employees فقط
- استبعاد department_manager

3. التصويت:
- إذا voter role = department_manager:
  - candidate pool = نفس القسم فقط

4. مدير الشهر:
- احسب top4 بعد الاعتماد.
- الفائز مدير واحد فقط.
- الشرط الأساسي: 3 من 4 من نفس القسم.
- tie-break:
  - أعلى متوسط final_score
  - ثم أقل late_minutes
  - ثم الأسبق تعيينا كمدير قسم

## 3.4 إيقاف إعلان النتائج

1. إضافة flag حالة نشر للنتيجة النهائية (مثلا results_published_at أو is_finalized).
2. أي صفحة عامة تعرض فقط:
- draft message قبل الاعتماد.
- النتائج بعد الاعتماد الرسمي فقط.

## 3.5 منطق الإجازات

1. LeaveEligibilityService:
- حساب أيام الخدمة التقويمية من employment_start_date.
- منع التقديم إذا أقل من required_work_days_before_leave.

2. LeaveRequestService:
- submit request
- route to HR + department manager (حسب الحالة)

3. Approval Service:
- HR approve/reject/partial
- Department manager approve/reject فقط
- إذا رفض أي طرف => status rejected + request closed
- إذا لا يوجد مدير قسم => قرار HR يحسم الطلب

4. Balance Service:
- حساب remaining_days وتحديث used_days عند اعتماد نهائي.

مخرجات المرحلة:
- المنطق الأساسي كامل على مستوى backend.

---

## Phase 4 - Authorization (Policies + Middleware)

1. إنشاء policies واضحة:
- DepartmentPolicy
- LeaveRequestPolicy
- EmployeeVisibilityPolicy
- DailyPerformanceReviewPolicy
- TaskAssignmentPolicy

2. تحديث routes/web.php:
- إضافة مسارات الأقسام.
- إضافة مسارات الإجازات للموظف + HR + مدير القسم.
- ضبط middlewares للأدوار الجديدة.

3. منع أي وصول خارج القسم لمدير القسم في كل Endpoint.

مخرجات المرحلة:
- الصلاحيات مضبوطة ومحمية.

---

## Phase 5 - واجهات المستخدم (Blade)

## 5.1 عرض الاسم/الوظيفة/القسم

1. تحديث بطاقات/جداول المستخدمين.
2. الصيغة:
- موظف عادي: {الوظيفة} ({القسم})
- مدير قسم: مدير قسم {اسم القسم}

## 5.2 واجهات الأقسام

1. صفحة قائمة الأقسام.
2. إنشاء/تعديل قسم.
3. اختيار مدير القسم.
4. إضافة/إزالة موظفين من القسم.

## 5.3 واجهات الوظائف الديناميكية

1. قسم جديد في الإعدادات أو صفحة مستقلة:
- إضافة وظيفة جديدة.
- تفعيل/تعطيل وظيفة.

2. في create/edit employee:
- اختيار وظيفة من القائمة الديناميكية.
- خيار إضافة وظيفة جديدة (لـ HR/Admin/Manager حسب السياسة).

## 5.4 واجهات مدير القسم

1. Dashboard خاص مختصر:
- عدد أعضاء القسم
- حضور/غياب
- متوسط تقييم الأداء اليومي
- حالة المهام

2. Daily performance review:
- عرض أعضاء القسم فقط.

3. Tasks:
- إسناد/متابعة مهام القسم فقط.

## 5.5 واجهات أفضل موظف/أفضل مدير

1. صفحة التصويت:
- مدير القسم يرى أعضاء قسمه فقط.

2. صفحة النتائج:
- عرض Top4 بعد الاعتماد فقط.
- إضافة بلوك مدير الشهر (فائز واحد فقط).

3. قبل الاعتماد:
- رسالة واضحة: النتائج لم تعتمد بعد.

## 5.6 واجهات الإجازات

1. صفحة طلب إجازة للموظف:
- Calendar + range + reason.
- عرض eligibility + المتبقي.
- عرض الرصيد السنوي + المستخدم + المتبقي.
- عرض تاريخ الطلبات السابقة.

2. صفحة إدارة الطلبات لـ HR/مدير القسم:
- جدول الطلبات.
- تفاصيل الطلب.
- قرارات + ملاحظات.
- إظهار status لكل طرف داخل بطاقة الطلب.

مخرجات المرحلة:
- UX مكتمل ومطابق للمطلوب.

---

## Phase 6 - Refactor ودمج مع المنظومة الحالية

1. استبدال كل استخدامات job_title enum تدريجيا بـ relation jobTitle.
2. تحديث EmployeeAccountService لاشتقاق الدور بشكل صحيح مع department_manager.
3. مراجعة أي queries تعتمد role=employee في التصويت والمهام.
4. مراجعة dashboard services لضمان عدم تسريب بيانات خارج القسم.

مخرجات المرحلة:
- النظام القديم والجديد يعملان بدون تعارض.

---

## Phase 7 - الاختبارات

## 7.1 Unit Tests

1. Eligibility calculations.
2. Leave approvals matrix.
3. Top4 + min90 rules.
4. Best manager winner selection.

## 7.2 Feature Tests

1. Department manager visibility boundaries.
2. Daily performance review permissions.
3. Vote scope by department manager.
4. Result publish gating.
5. Leave request full lifecycle.

## 7.3 Regression Tests

1. Admin/HR/Manager full access still valid.
2. Existing employee flows unaffected.
3. Existing import/payroll flows unaffected.

مخرجات المرحلة:
- ثقة عالية قبل الإطلاق.

---

## Phase 8 - الإطلاق (Release)

1. أخذ backup database.
2. تشغيل migrations على staging.
3. تنفيذ smoke tests كاملة.
4. نشر تدريجي production.
5. متابعة logs والأخطاء أول 48 ساعة.

مخرجات المرحلة:
- إطلاق آمن ومستقر.

---

## 4) ترتيب التنفيذ المقترح (Sprint Plan)

## Sprint 1
- DB + Models + Relations (departments, job_titles, leave tables)

## Sprint 2
- Authorization + department scopes + department manager promotion logic

## Sprint 3
- UI الأقسام + UI الوظائف + عرض الاسم/الوظيفة/القسم

## Sprint 4
- Daily performance/tasks/attendance scoped for department manager

## Sprint 5
- Employee of month updates (Top4, min90, vote scope, best manager, publish gating)

## Sprint 6
- Leave requests end-to-end + settings + balances

## Sprint 7
- Tests + bug fixes + launch

---

## 5) Definition of Done

يعتبر التنفيذ منتهيا فقط إذا:

1. لا يوجد endpoint يسمح لمدير قسم برؤية موظف خارج قسمه.
2. Manager القديم يظل بصلاحياته الكاملة دون تراجع.
3. Top4 + min90 مطبق فعليا في النتائج.
4. مدير القسم يستطيع التصويت داخل قسمه فقط.
5. مدير الشهر يظهر فائز واحد فقط وفق القواعد.
6. لا إعلان للنتيجة قبل الاعتماد.
7. الإجازات تعمل بكل المسارات المطلوبة (HR-only / HR+Manager / partial / reject-close).
8. اختبارات القبول ناجحة.
