# خطة تنفيذ الفيتشر الأولى: حسابات الموظفين والملف الشخصي

تاريخ الوثيقة: 2026-03-25
النطاق: Feature 1 فقط

## 1) الهدف

تنفيذ نظام حساب لكل موظف بحيث:
- Admin ينشئ الحساب.
- البريد يُولّد تلقائيا بصيغة: name@perfection.com.
- البريد غير قابل للتعديل.
- كلمة السر الابتدائية ثابتة: 123456789.
- أول دخول يفرض تغيير كلمة السر.
- إضافة بيانات الملف الشخصي: صورة + Bio + رابطين تواصل.
- إضافة Job Title كـ Enum مع عرض عربي.
- Manager و HR نفس صلاحيات وواجهة Admin بالكامل.

---

## 2) القرارات المعتمدة

- Job Title: Enum قابل للتوسعة لاحقا + عرض عربي في الواجهة.
- Email: readonly ويتم إنشاؤه تلقائيا.
- Password initial: 123456789 (مع hashing).
- لا يوجد إرسال إيميل تلقائي، عرض بيانات الدخول داخل لوحة Admin فقط.
- Manager و HR = Admin في الصلاحيات والواجهة.

---

## 3) نطاق البيانات المطلوب (Database)

## 3.1 جدول users

- تعديل role enum ليصبح:
  - admin
  - manager
  - hr
  - employee
- إضافة:
  - employee_id (nullable, unique, FK -> employees.id)
  - must_change_password (boolean, default true)
  - last_password_changed_at (nullable datetime)

## 3.2 جدول employees

- إضافة job_title Enum بالقيم الابتدائية:
  - designer
  - 3D
  - customer_service
  - developer
  - hr
  - admin
  - manager

## 3.3 جدول جديد user_profiles

- user_id (unique FK)
- avatar_path (nullable)
- bio (nullable)
- social_link_1 (nullable)
- social_link_2 (nullable)

---

## 4) قواعد الأعمال

- عند إنشاء/تعديل الوظيفة:
  - job_title=admin => role=admin
  - job_title=manager => role=manager
  - job_title=hr => role=hr
  - غير ذلك => role=employee
- البريد الإلكتروني:
  - slug(name)+@perfection.com
  - ضمان uniqueness عند التكرار (مثال: omar2@perfection.com)
- أول دخول:
  - المستخدم لا يستطيع الوصول لباقي النظام قبل تغيير كلمة السر.

---

## 5) خطوات التنفيذ (Step-by-Step)

1. إنشاء Migrations
- Migration لتوسيع role وإضافة employee_id وحقول تغيير كلمة السر في users.
- Migration لإضافة job_title في employees.
- Migration لإنشاء user_profiles.

2. تحديث Models والعلاقات
- User:
  - belongsTo(Employee)
  - hasOne(UserProfile)
  - helper: isAdminLike()
- Employee:
  - hasOne(User)
- UserProfile:
  - belongsTo(User)

3. تنفيذ منطق توليد الحساب
- Service/Action لإنشاء حساب الموظف من لوحة Admin.
- توليد email تلقائيا مع uniqueness.
- ضبط password الافتراضية + must_change_password=true.
- حفظ role تلقائيا بناء على job_title.

4. تنفيذ فرض تغيير كلمة السر أول دخول
- إنشاء Middleware: ForcePasswordChange.
- ربطه في Kernel + routes المحمية.
- استثناء شاشة تغيير كلمة السر وتسجيل الخروج فقط.
- بعد نجاح التغيير: must_change_password=false و last_password_changed_at=now.

5. إنشاء شاشة "حسابي"
- قسم بيانات أساسية (الاسم/الوظيفة/البريد readonly).
- قسم تعديل الصورة + bio + رابطين تواصل.
- قسم تغيير كلمة السر.
- قسم إحصائيات شهرية readonly (إعادة استخدام AbsenceDetectionService).

6. توحيد الصلاحيات والواجهة
- استبدال أي شروط isAdmin إلى isAdminLike حيث يلزم.
- تعديل authorize في FormRequests.
- إظهار نفس عناصر واجهة Admin بالكامل لـ manager/hr.

7. تحديث واجهات إدارة الموظفين
- إضافة اختيار الوظيفة Job Title في create/edit.
- إضافة زر/إجراء إنشاء حساب للموظف (أو إنشاء تلقائي عند الحفظ حسب قرار التنفيذ).
- عرض بيانات الدخول الابتدائية داخل لوحة Admin.

8. اختبارات Feature 1
- اختبار إنشاء حساب + ربط employee_id.
- اختبار توليد email وعدم قابلية التعديل.
- اختبار فرض تغيير كلمة السر أول دخول.
- اختبار صلاحيات manager/hr = admin-like.
- اختبار تحديث بيانات الملف الشخصي.

---

## 6) معايير القبول (Acceptance Criteria)

- أي موظف لديه حساب دخول مرتبط ب employee_id.
- البريد يظهر readonly وغير قابل للتغيير عبر UI أو API.
- المستخدم الجديد يجبر على تغيير كلمة السر قبل أي شاشة أخرى.
- يمكنه تعديل الصورة/Bio/روابط التواصل فقط.
- name وjob_title غير قابلين للتعديل من الموظف.
- manager/hr يدخلان نفس واجهة وصلاحيات admin.

---

## 7) ترتيب تسليم الفيتشر الأولى

- A1: DB + Models + Relations
- A2: Account Provisioning + Email/Password Rules
- A3: Force First Login Password Change
- A4: My Account UI + Profile Data
- A5: Roles/Permissions Refactor + QA
