# خطة التعديلات الجديدة على فيتشر موظف الشهر + نظام المهام

تاريخ الوثيقة: 2026-03-25
نطاق الوثيقة: التعديلات المطلوبة بعد تنفيذ Feature 2 حتى المرحلة 6

## 1) ملخص المطلوب الجديد

تم طلب إضافة وتعديل النقاط التالية:

1. صفحة Admin جديدة باسم "المهام":
- إنشاء مهمة جديدة.
- إسناد المهمة لموظف واحد أو أكثر.
- قائمة المهام تكون شهرية بنفس دورة النظام (22 -> 21).

2. إضافة Role جديد باسم `user`:
- وظيفته فقط تقييم المهام.
- عند الدخول يرى قائمة المهام (اسم المهمة فقط بدون معرفة الموظف المسند له).
- يضع تقييم من 1 إلى 10 + ملاحظة اختيارية.

3. صفحة "مهامي" للموظف:
- يرى المهام المسندة له.
- يرى تقييم المهمة والملاحظة (إن وجدت).

4. تعديل صفحة Admin لموظف الشهر:
- إزالة "تقييم الإدارة (1-5)" بالكامل.
- استبدالها بعامل "تقييم المهام" (إجمالي تقييمات المهام لكل موظف خلال الشهر).

5. تعديل معادلة موظف الشهر لتكون فقط:
- التاسكات: 40%
- الحضور المبكر (أقل late minutes): 15%
- عدد ساعات العمل: 20%
- تصويت الموظفين: 25%

6. مطلوب طريقة حساب عادلة:
- لا أحد يحصل على 0.
- منع Over-penalizing.
- معالجة Outliers بشكل لا يظلم أحد.

7. إضافة تصدير Excel:
- تصدير بيانات موظف الشهر من صفحة Admin بشكل منسق.
- تصدير المهام وتقييماتها بشكل منسق.

---

## 2) الوضع الحالي (المنفذ بالفعل)

من واقع التنفيذ الحالي حتى المرحلة 6:

- تم تنفيذ التصويت الشهري مع حماية Race Condition.
- تم تنفيذ صفحة تصويت الموظف + countdown + You already voted.
- تم تنفيذ لوحة Admin لموظف الشهر (نتائج + مؤشرات + ترتيب + history).
- تم استخدام عامل AdminScore في الحساب الحالي.

بالتالي: المطلوب الآن هو "Delta" على المنفذ، وليس إعادة بناء من الصفر.

---

## 3) التغييرات المطلوبة في قاعدة البيانات

## 3.1 إضافة Role جديد

- توسيع role في users ليشمل القيمة `user`.
- تحديث أي validation/enum/helper مرتبط بالأدوار.
- صلاحيات `user`:
  - مسموح: صفحة حسابه.
  - مسموح: صفحة الموظفين بنفس عرض الكروت الخاص بالموظف.
  - مسموح: صفحة تقييم المهام.
  - غير مسموح: شاشات الإدارة الأخرى.

## 3.2 جداول نظام المهام

1) `employee_month_tasks`
- id
- title
- description (nullable)
- period_month (tinyint)
- period_year (smallint)
- period_start_date (date)  // غالبا 22 من الشهر السابق
- period_end_date (date)    // غالبا 21 من الشهر الحالي
- created_by (FK users)
- is_active (boolean, default true)
- created_at, updated_at

فهارس مقترحة:
- index(period_year, period_month)
- index(is_active)

2) `employee_month_task_assignments`
- id
- task_id (FK employee_month_tasks)
- employee_id (FK employees)
- created_at, updated_at
- unique(task_id, employee_id)

فهارس مقترحة:
- index(employee_id)

3) `employee_month_task_evaluations`
- id
- task_id (FK employee_month_tasks)
- evaluator_user_id (FK users) // role=user
- score (tinyint: 1..10)
- note (nullable)
- created_at, updated_at
- unique(task_id)  // مهمة واحدة = تقييم واحد فقط

فهارس مقترحة:
- index(score)

ملاحظة:
- المقيم `user` لا يرى employee_id مباشرة من واجهة التقييم.
- العلاقة الفعلية بالموظف تأتي عبر assignment في الخلفية فقط.
- يسمح بتعديل تقييم المهمة من نفس user المقيم الذي قام بالحفظ (update لنفس السجل).

---

## 4) تعديلات الدومين والخدمات

## 4.1 خدمات جديدة مقترحة

1. `TaskManagementService`
- إنشاء/تعديل/تعطيل مهمة.
- إسناد المهمة لموظف/عدة موظفين.
- ربط المهمة بدورة شهر الراتب 22->21.

2. `TaskEvaluationService`
- إتاحة المهام للمقيم role=user فقط.
- حفظ/تحديث تقييم 1..10 + note.
- منع رؤية بيانات الموظف للمقيم.
- منع تعدد المقيمين لنفس المهمة (task_id unique).

3. `EmployeeTaskScoreService`
- تجميع تقييمات المهام لكل موظف شهريا.
- استبعاد المهام غير المقيمة من الحساب.
- حساب `TaskScore` النهائي (0-100 قبل تطبيق floor).

## 4.2 تعديل خدمات موظف الشهر الحالية

1. تعديل `EmployeeOfMonthMetricsService`
- إضافة task metrics لكل موظف.
- إزالة الاعتماد على admin score من الحساب النهائي.

2. تعديل `EmployeeOfMonthScoringService`
- المعايير الفعالة تصبح فقط:
  - tasks
  - punctuality
  - work_hours
  - vote
- إزالة admin/overtime من المعادلة الفعالة (يمكن إبقاء التوافق الخلفي إن لزم).

3. إبقاء `EmployeeOfMonthFinalizationService`
- مع تحديث breakdown لاحتواء task score بدل admin score.

---

## 5) UX/UI المطلوبة

## 5.1 صفحة Admin: المهام

- عرض مهام الشهر الحالي (22->21).
- إنشاء مهمة جديدة بسهولة.
- Multi-select للموظفين عند الإسناد.
- عرض عدد الموظفين المسند لهم كل مهمة.
- عرض عدد التقييمات المستلمة لكل مهمة.
- أنيميشن خفيفة متسقة مع الهوية الحالية (fade/slide/hover).

## 5.2 صفحة role=user: تقييم المهام

- يرى فقط:
  - اسم المهمة
  - الوصف (اختياري)
  - حقل score (1..10)
  - حقل note
- لا يظهر له من المسند للمهمة.
- واجهة بسيطة وسريعة ومتحركة بخفة.
- يسمح له بتعديل تقييمه لنفس المهمة بعد الحفظ.

## 5.2.1 صفحات إضافية role=user

- صفحة الحساب الشخصي (مسموح).
- صفحة الموظفين بنفس نمط الكروت الخاص بالموظف (بدون صلاحيات إدارية).

## 5.3 صفحة الموظف: مهامي

- قائمة المهام المسندة له في دورة الشهر.
- لكل مهمة:
  - متوسط/إجمالي التقييم حسب القاعدة المعتمدة
  - الملاحظة (إن وجدت)
  - حالة وجود تقييم من عدمه
- عرض كل الملاحظات المرتبطة بالمهمة (لأنها بسيطة).

## 5.4 تعديل لوحة Admin موظف الشهر

- إزالة بلوك تقييم الإدارة.
- إضافة بلوك "تقييم المهام".
- تحديث جدول الترتيب النهائي ليعرض:
  - TaskScore
  - VoteScore
  - WorkHoursScore
  - PunctualityScore

## 5.5 التصدير Excel

1. تصدير موظف الشهر (Admin):
- ملف منسق يحتوي:
  - ترتيب الموظفين.
  - الدرجات التفصيلية لكل معيار.
  - الأوزان المستخدمة.
  - الدرجة النهائية.
  - نسخة المعادلة formula_version.

2. تصدير المهام والتقييمات (Admin):
- ملف منسق يحتوي:
  - بيانات المهمة.
  - الموظفون المسند لهم.
  - التقييم (score).
  - الملاحظات.
  - المقيم وتاريخ آخر تعديل.

---

## 6) معادلة موظف الشهر الجديدة

## 6.1 الأوزان

- TaskScore = 40%
- PunctualityScore = 15%
- WorkHoursScore = 20%
- VoteScore = 25%

$$
Score_{Final} = 0.40 \cdot TaskScore + 0.15 \cdot PunctualityScore + 0.20 \cdot WorkHoursScore + 0.25 \cdot VoteScore
$$

## 6.2 عدالة الحساب (منع 0 + معالجة outliers)

لمنع الظلم وOver-penalizing نقترح:

1. Robust Clipping (Winsorization)
- قص القيم عند P10 و P90 لكل معيار رقمي قبل التطبيع.

2. Min Score Floor
- أي معيار بعد التطبيع يتحول من نطاق [0,100] إلى [20,100].
- بذلك لا يأخذ أي موظف صفرا مباشرا بسبب معيار واحد.

3. Neutral Fallback
- إذا التباين معدوم (مثلا كل late minutes متساوية) تعطى قيمة محايدة للجميع (مثلا 60).

4. استبعاد غير المقيم
- أي مهمة بلا تقييم لا تدخل في `TaskScore` (لا تعتبر 0).

4. معالجة معيار late minutes (الأقل أفضل)
- استخدام معادلة عكسية بعد clipping، وليس عقوبة خطية حادة.

صيغة تطبيع مقترحة:

للأعلى أفضل:
$$
Norm = \frac{x_{clipped} - P10}{P90 - P10}
$$

للأقل أفضل (late minutes):
$$
Norm = \frac{P90 - x_{clipped}}{P90 - P10}
$$

ثم:
$$
Score = 20 + 80 \cdot clamp(Norm, 0, 1)
$$

---

## 7) التعديلات على الإعدادات (settings)

تحديث مفاتيح الأوزان إلى:
- employee_of_month.weights.tasks = 0.40
- employee_of_month.weights.punctuality = 0.15
- employee_of_month.weights.work_hours = 0.20
- employee_of_month.weights.vote = 0.25

وتحديث criteria:
- tasks
- punctuality
- work_hours
- vote

---

## 8) خطة تنفيذ مختصرة (Delta Roadmap)

1. DB Layer
- Migration role=user.
- Migrations لجداول tasks/assignments/evaluations.
- ضبط unique(task_id) في جدول evaluations لمنع تعدد المقيمين لنفس المهمة.

2. Models + Relations
- EmployeeMonthTask
- EmployeeMonthTaskAssignment
- EmployeeMonthTaskEvaluation
- إضافة العلاقات اللازمة في User/Employee.

3. Service Layer
- TaskManagementService
- TaskEvaluationService
- EmployeeTaskScoreService
- تعديل Scoring/Metrics الحالية.

4. UI Layer
- Admin Tasks Page.
- User Evaluator Page.
- Employee My Tasks Page.
- تعديل Admin Employee-of-Month Dashboard.
- إضافة فلاتر فترة 22->21 في كل الصفحات ذات الصلة.
- إضافة Coverage Indicators في لوحة Admin.
- إضافة Explain Score في لوحة Admin.

5. Export Layer
- Export: Employee of Month Ranking.
- Export: Tasks + Assignments + Evaluations.

6. Tests
- صلاحيات role=user.
- تدفق إنشاء/إسناد/تقييم المهام.
- خصوصية المقيم (لا يرى الموظف المسند).
- حساب TaskScore وتجميعه لكل موظف.
- صحة المعادلة الجديدة + fairness floor + outlier handling.
- اختبار استبعاد المهام غير المقيمة من الحساب.
- اختبارات التصدير Excel.

---

## 9) قرارات محسومة (حسب إجاباتك)

1. role `user` يرى:
- صفحة حسابه.
- صفحة الموظفين بنمط الكروت مثل الموظف.
- صفحة تقييم المهام.

2. التقييم:
- لا يوجد أكثر من user يقيم نفس المهمة.
- مسموح تعديل التقييم من نفس role user.

3. الملاحظات:
- عرض كل الملاحظات لأنها بسيطة.

4. مهمة غير مقيمة:
- تستبعد من حساب TaskScore.

5. اعتماد النتائج:
- يدوي من Admin + تلقائي عبر Job يوم 21 (يدعم الاثنين).

6. الاقتراحات:
- الاحتفاظ بـ admin_scores حاليا (بدون حذف الآن).
- إضافة فلتر الفترة.
- إضافة coverage indicators.
- إضافة Explain Score.

7. التصدير:
- مطلوب ومُعتمد لبيانات موظف الشهر والمهام/التقييمات.

---

## 10) خطة تنفيذ واضحة (تفصيلية)

## المرحلة A: قاعدة البيانات والصلاحيات

1. إضافة role `user` في users role enum.
2. إنشاء جداول:
- employee_month_tasks
- employee_month_task_assignments
- employee_month_task_evaluations
3. تحديث seed/default settings للأوزان والمعايير الجديدة.

## المرحلة B: النماذج والعلاقات

1. إضافة Models الجديدة + العلاقات مع User/Employee.
2. تحديث User helpers:
- isEmployee()
- isAdminLike()
- isEvaluatorUser() (جديد)
3. تحديث Middleware/Routes لصلاحيات user.

## المرحلة C: الخدمات والحساب

1. تنفيذ TaskManagementService.
2. تنفيذ TaskEvaluationService.
3. تنفيذ EmployeeTaskScoreService مع:
- استبعاد المهام غير المقيمة.
- winsorization + floor + fallback.
4. تعديل EmployeeOfMonthMetricsService لإدخال Task metrics.
5. تعديل EmployeeOfMonthScoringService للمعادلة الجديدة فقط.
6. تحديث EmployeeOfMonthFinalizationService بالـ breakdown الجديد.

## المرحلة D: الواجهات

1. Admin Tasks Page:
- CRUD خفيف + assignment متعدد + فلتر فترة.
- عرض coverage.
2. Evaluator User Page:
- قائمة مهام بدون أي بيانات موظف.
- تقييم 1..10 + note + إمكانية التعديل.
3. Employee My Tasks Page:
- مهامه + التقييم + كل الملاحظات.
4. Admin Employee-of-Month Dashboard:
- إزالة AdminScore UI.
- إضافة TaskScore + Explain Score + الفلاتر.

## المرحلة E: التشغيل الآلي + التصدير

1. Job تلقائي يوم 21 لاعتماد النتائج (مع بقاء الزر اليدوي).
2. Export Employee of Month Excel (منسق).
3. Export Tasks + Evaluations Excel (منسق).

## المرحلة F: الاختبارات وضمان الجودة

1. Feature tests للصلاحيات الجديدة (admin/employee/user).
2. Feature tests لتدفق المهام والتقييم.
3. Tests للخصوصية (المقيم لا يرى المسند له المهمة).
4. Tests للمعادلة الجديدة وعدالتها (outlier/floor/no-zero).
5. Tests للتصدير.

معيار الإغلاق:
- كل الاختبارات تمر.
- لا Regression في Features السابقة.
- التصدير يعمل والملفات تفتح بشكل صحيح.
