# خطة تنفيذ الفيتشر الثانية: موظف الشهر (تصويت + تقييم + مؤشرات)

تاريخ الوثيقة: 2026-03-25
النطاق: Feature 2 فقط

## 1) الهدف

تنفيذ نظام "موظف الشهر" بحيث:
- الموظفون المؤهلون يصوتون مرة واحدة فقط شهريا.
- التصويت متاح حتى نهاية يوم 20 من كل شهر.
- لا يمكن التصويت للنفس.
- لا يمكن التراجع بعد التصويت.
- admin/manager/hr لا يصوتون ولا يكونون مرشحين.
- Admin يرى النتائج + مؤشرات الأداء + تقييمه اليدوي.
- استخدام معادلة نهائية قابلة للتوسعة لاحقا.
- حفظ نتائج كل شهر بشكل تاريخي (History) للرجوع والتدقيق.
- تجربة مستخدم واضحة: حالة التصويت الحالية + عد تنازلي حتى إغلاق التصويت.
- تعديل تقرير الحضور: استبدال نسبة الحضور بعدد ساعات العمل.

---

## 2) القرارات المعتمدة

- المؤهل للتصويت = role: employee فقط.
- المرشح للتصويت = role: employee فقط.
- "الأكثر حضورا مبكرا" = أقل إجمالي دقائق تأخير في الشهر.
- تقييم Admin من 1 إلى 5.
- يوجد معادلة نهائية لاختيار الموظف المثالي.
- سيتم إضافة تقييمات أخرى لاحقا، لذا يجب بناء النظام قابل للتوسعة.
- منع Race Condition في التصويت إلزامي: unique index مهم لكنه غير كاف بمفرده.
- أوزان المعادلة والـ criteria يجب أن تكون قابلة للزيادة/التعديل بدون كسر كبير للكود.

---

## 3) نطاق البيانات المطلوب (Database)

## 3.1 جدول employee_month_votes

- id
- voter_user_id (FK users)
- voted_employee_id (FK employees)
- vote_month (tinyint)
- vote_year (smallint)
- created_at
- unique(voter_user_id, vote_month, vote_year)

ملاحظات تنفيذية مهمة:
- الإبقاء على unique index كطبقة حماية نهائية على مستوى قاعدة البيانات.
- إضافة فهرس مركب للاستعلامات الإدارية: (vote_year, vote_month, voted_employee_id).

## 3.2 جدول employee_month_admin_scores

- id
- employee_id (FK employees)
- month
- year
- score (1..5)
- note (nullable)
- created_by (FK users)
- unique(employee_id, month, year)

## 3.3 جدول employee_of_month_results (History)

- id
- employee_id (FK employees)
- month (tinyint)
- year (smallint)
- final_score (decimal 8,2)
- breakdown (json)  // تخزين تفاصيل الدرجات بعد التطبيع والأوزان المطبقة
- formula_version (string)  // مثال: v1
- generated_at (timestamp)
- unique(employee_id, month, year)

محتوى breakdown المقترح (قابل للزيادة):
- vote_score
- admin_score
- work_hours_score
- punctuality_score
- overtime_score
- weights_applied
- raw_inputs

## 3.4 إعدادات الأوزان (settings)

- حفظ أوزان المعادلة في settings لتسهيل إضافة معايير لاحقا بدون تغييرات كبيرة في الكود.
- مثال مفاتيح:
  - employee_of_month.weights.vote
  - employee_of_month.weights.admin
  - employee_of_month.weights.work_hours
  - employee_of_month.weights.punctuality
  - employee_of_month.weights.overtime
  - employee_of_month.formula_version
- يفضل توفير مصفوفة criteria في الإعدادات لتمكين إضافة معيار جديد مستقبلا بدون تعديل كبير في الخدمة.

---

## 4) قواعد الأعمال

- نافذة التصويت: من أول الشهر حتى 20 23:59:59.
- من يوم 21: التصويت مغلق.
- كل مستخدم مؤهل له صوت واحد فقط للشهر.
- لا تصويت للنفس.
- لا يمكن تعديل/حذف التصويت بعد الحفظ.
- admin/manager/hr مستبعدون من التصويت والترشيح.
- يجب التعامل مع الضغط المتزامن (double-click / retry / latency) بحيث لا يتم تسجيل أكثر من صوت لنفس المستخدم.
- عند محاولة تصويت مكرر بالتزامن: ترجع استجابة واضحة أن المستخدم صوّت بالفعل (بدون كسر UX).
- يتم تجميد النتائج الشهرية في جدول History بعد اعتماد/إغلاق الشهر مع إمكانية إعادة توليد محسوبة (regenerate) بشكل مقيد للصلاحيات.

---

## 5) خطوات التنفيذ (Step-by-Step)

1. إنشاء Migrations
- إنشاء جدول employee_month_votes مع unique index الشهري.
- إنشاء جدول employee_month_admin_scores مع score constraints.
- إنشاء جدول employee_of_month_results لحفظ النتائج التاريخية.
- إضافة settings keys لأوزان المعادلة.

2. إنشاء Models والعلاقات
- EmployeeMonthVote model.
- EmployeeMonthAdminScore model.
- EmployeeOfMonthResult model.
- علاقات مع User وEmployee.

3. بناء طبقة الخدمة (Service Layer)
- VoteEligibilityService للتحقق من صلاحية التصويت.
- VoteSubmissionService لمعالجة التصويت داخل Transaction بشكل ذري (atomic).
- EmployeeOfMonthMetricsService لحساب المؤشرات:
  - total work hours (sum(work_minutes))
  - punctuality (أقل late_minutes)
  - overtime (sum(overtime_minutes))
- EmployeeOfMonthScoringService لحساب الدرجة النهائية.
- EmployeeOfMonthFinalizationService لحفظ snapshot النتائج في History.

تفاصيل معالجة Race Condition (مهم):
- استخدام DB transaction في عملية التصويت كاملة.
- قفل صف المستخدم المصوت قبل التحقق النهائي (select ... for update على سجل users أو lock مخصص للمصوت).
- إعادة التحقق داخل transaction من عدم وجود تصويت سابق لنفس (voter_user_id, month, year).
- محاولة الإدراج ثم التقاط خطأ unique constraint وتحويله لاستجابة business-friendly: "You already voted".
- endpoint يكون idempotent من منظور العميل: أي إعادة إرسال لنفس الشهر تعطي نفس النتيجة المنطقية (already voted).

4. تنفيذ Endpoint التصويت
- Controller/Request للتصويت.
- validations:
  - المستخدم مؤهل.
  - داخل نافذة التصويت.
  - المرشح مؤهل.
  - ليس نفس الموظف.
  - لم يصوت سابقا بنفس الشهر.
- حفظ التصويت مرة واحدة بشكل نهائي.
- إرجاع payload فيه:
  - has_voted
  - voted_employee_id
  - voting_closes_at
  - seconds_remaining_to_close

5. شاشة الموظف للتصويت
- عرض المرشحين المؤهلين فقط.
- استبعاد الموظف الحالي.
- زر تصويت واحد.
- بعد التصويت: إظهار النتيجة الشخصية (تم التصويت) مع قفل النموذج.
- UX تحسينات إلزامية:
  - شارة واضحة: "You already voted" عند وجود تصويت.
  - Countdown حتى يوم 20 23:59:59 بحسب timezone النظام.
  - تعطيل زر التصويت أثناء الإرسال لمنع double submit.
  - في حالة الإغلاق (بعد يوم 20): رسالة واضحة "Voting is closed for this month".

6. شاشة Admin "موظف الشهر"
- بلوك نتائج التصويت:
  - ترتيب الموظفين حسب عدد الأصوات.
  - عدد المصوتين.
- بلوك مؤشرات الأداء:
  - أعلى ساعات عمل.
  - الأكثر حضورا مبكرا (أقل late_minutes).
  - أعلى overtime.
- بلوك تقييم Admin:
  - إدخال/تعديل score من 1 إلى 5 لكل موظف.
- بلوك النتيجة النهائية:
  - عرض الترتيب النهائي بناء على المعادلة.
- بلوك History:
  - عرض نتائج الشهور السابقة من جدول employee_of_month_results.
  - إظهار formula_version و breakdown لكل شهر (عند الطلب أو modal).

7. المعادلة النهائية (v1)

Score_Final = 0.40 * VoteScore + 0.25 * AdminScore + 0.20 * WorkHoursScore + 0.10 * PunctualityScore + 0.05 * OvertimeScore

تعريف الدرجات بعد التطبيع إلى 100:
- VoteScore = (employee_votes / total_valid_votes) * 100
- AdminScore = (admin_score / 5) * 100
- WorkHoursScore = (employee_work_hours / max_work_hours) * 100
- PunctualityScore = (1 - employee_late_minutes / max_late_minutes) * 100
  - إذا max_late_minutes = 0 => 100 للجميع
- OvertimeScore = (employee_ot_hours / max_ot_hours) * 100
  - إذا max_ot_hours = 0 => 0 للجميع

قواعد توسعة المعادلة:
- لا يتم hard-code للأوزان داخل الكود؛ القراءة من settings فقط.
- أي criterion جديد يضاف عبر configuration + scorer class/strategy جديدة.
- يفضل اعتماد Pattern مثل: CriterionScoreInterface + ScoreCalculatorRegistry.
- تسجيل formula_version داخل history حتى يمكن مقارنة نتائج الشهور عند تغير الأوزان/المعايير.


8. اختبارات Feature 2
- لا يمكن التصويت مرتين في نفس الشهر.
- لا يمكن التصويت للنفس.
- لا يمكن التصويت بعد يوم 20.
- admin/manager/hr لا يظهرون كمرشحين.
- حساب المؤشرات صحيح.
- حساب المعادلة النهائية صحيح.
- اختبار تزامن: طلبان متوازيان لنفس المستخدم في نفس الشهر -> صوت واحد فقط محفوظ.
- اختبار graceful duplicate: إذا حصل unique violation ترجع رسالة "already voted".
- اختبار حفظ history: بعد finalization يتم إدراج صف لكل موظف بالـ breakdown الصحيح.
- اختبار countdown/status endpoint values (has_voted + seconds_remaining_to_close).

---

## 6) معايير القبول (Acceptance Criteria)

- كل موظف مؤهل يصوت مرة واحدة فقط قبل يوم 21.
- لا توجد طريقة لتعديل التصويت بعد الحفظ.
- عند الضغط المزدوج أو إعادة المحاولة الشبكية لا يحدث تكرار أصوات (Race Condition handled).
- صفحة Admin تعرض:
  - نتائج التصويت + عدد المصوتين
  - أعلى ساعات عمل
  - الأقل تأخير (الأكثر حضورا مبكرا)
  - أعلى overtime
  - تقييم Admin 1..5
  - الترتيب النهائي حسب المعادلة
- النظام يحفظ نتائج كل شهر في جدول History مع breakdown وformula_version.
- واجهة الموظف تعرض حالة التصويت "You already voted" + countdown حتى إغلاق التصويت.
- تقرير الحضور لا يعرض نسبة الحضور ويعرض ساعات العمل بدلا منها.

---

## 7) ترتيب تسليم الفيتشر الثانية

- B1: DB + Models + Vote Rules
- B2: Employee Voting UI + Endpoint
- B3: Admin Employee-of-Month Dashboard
- B4: Scoring Formula + Settings Weights + Extensible Criteria Engine
- B5: Monthly Finalization + History Snapshot
- B6: Attendance Report Column Change + QA + Concurrency Tests

---

## 8) أسئلة مفتوحة قبل التنفيذ

- هل "اعتماد النتائج الشهرية" سيتم تلقائيا يوم 21 (job) أم يدويًا بواسطة Admin؟
- عند تعديل Admin Score بعد اعتماد الشهر: هل نمنع التعديل أم نسمح بإعادة توليد نسخة جديدة من النتائج؟
- timezone الرسمي للحسابات (UTC أم توقيت محلي للشركة) لتفادي اختلاف countdown والإغلاق؟
- هل نحتاج حفظ top winner فقط في history أم جميع الموظفين مع درجاتهم لكل شهر؟ (الموصى به: الجميع)
- هل نحتاج API منفصل لحالة التصويت/العد التنازلي لتحديث الواجهة بشكل دوري؟
