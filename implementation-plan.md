# 📋 خطة تنفيذ نظام الحضور والانصراف - Implementation Plan

> **إصدار الوثيقة:** 1.3  
> **تاريخ الإنشاء:** 2026-03-11  
> **آخر تحديث:** 2026-03-14 — إضافة الإجازات الرسمية، Dynamic Settings، Hard Delete، مراجعة OT (17:30)  
> **التقنيات:** Laravel 10 | PHP 8.1 | MySQL  
> **الحالة:** مرحلة التخطيط والتصميم

---

## 📑 فهرس المحتويات

1. [تحليل النظام](#1--تحليل-النظام)
2. [Architecture مقترحة للنظام](#2--architecture-مقترحة-للنظام)
3. [Database Schema](#3--database-schema)
4. [تصميم Modules للنظام](#4--تصميم-modules-للنظام)
5. [كيفية قراءة ملف Excel](#5--كيفية-قراءة-ملف-excel)
6. [خوارزمية حساب الحضور والتأخير](#6--خوارزمية-حساب-الحضور-والتأخير)
7. [خوارزمية حساب المرتبات](#7--خوارزمية-حساب-المرتبات)
8. [هيكل المشروع داخل Laravel](#8--هيكل-المشروع-داخل-laravel)
9. [أفضل طريقة لجعل النظام قابل للتوسع](#9--أفضل-طريقة-لجعل-النظام-قابل-للتوسع-scalable)
10. [Edge Cases المحتملة](#10--edge-cases-المحتملة)
11. [خطة تنفيذ Step-by-Step](#11--خطة-تنفيذ-step-by-step)

---

## 1. 📊 تحليل النظام

### 1.1 نظرة عامة

النظام عبارة عن تطبيق ويب لإدارة حضور وانصراف الموظفين، يعتمد على **استيراد ملفات Excel** المُصدَّرة من أجهزة البصمة، ثم يقوم بتحليل البيانات وحساب الرواتب تلقائياً.

### 1.2 الأطراف المعنية (Actors)

| الطرف | الصلاحيات |
|-------|-----------|
| **مدير النظام (Admin)** | إدارة كاملة: رفع ملفات، إدارة موظفين، حساب رواتب، إعدادات |
| **مشرف (Supervisor)** | عرض التقارير والبيانات فقط (مرحلة مستقبلية) |

### 1.3 المتطلبات الوظيفية (Functional Requirements)

| # | المتطلب | الأولوية |
|---|---------|----------|
| FR-01 | رفع ملف Excel من جهاز البصمة | عالية |
| FR-02 | قراءة وتحليل بيانات الحضور من الأعمدة A, B, C, D, E فقط | عالية |
| FR-03 | حساب أيام الحضور لكل موظف شهرياً | عالية |
| FR-04 | حساب أيام الغياب (مع مراعاة الإجازات) | عالية |
| FR-05 | حساب دقائق التأخير (من الساعة 9:00) | عالية |
| FR-06 | حساب دقائق الـ Overtime (بعد 17:30) | عالية |
| FR-07 | حساب المرتب النهائي | عالية |
| FR-08 | Dashboard شاملة | عالية |
| FR-09 | منع تكرار البيانات عند إعادة رفع نفس الشهر | عالية |
| FR-10 | إدارة بيانات الموظفين | متوسطة |
| FR-11 | صفحة إعدادات النظام | متوسطة |
| FR-12 | تقارير الحضور التفصيلية | متوسطة |
| FR-13 | إدخال الإجازات الرسمية (أعياد) بعد رفع الملف | عالية |
| FR-14 | تجاوز الإعدادات الافتراضية عند رفع كل ملف (Dynamic Settings) | متوسطة |
| FR-15 | حذف بيانات شهر كامل (Hard Delete) | متوسطة |

### 1.4 المتطلبات غير الوظيفية (Non-Functional Requirements)

| # | المتطلب |
|---|---------|
| NFR-01 | الأداء: معالجة ملف Excel يحتوي على 500 موظف في أقل من 30 ثانية |
| NFR-02 | الأمان: حماية البيانات بنظام Authentication كامل |
| NFR-03 | القابلية للتوسع: بنية نظيفة تسمح بإضافة ميزات جديدة بسهولة |
| NFR-04 | التوافق: PHP 8.1 و Laravel 10 |
| NFR-05 | قاعدة البيانات: MySQL 8.0+ |

### 1.5 قواعد العمل (Business Rules)

```
┌─────────────────────────────────────────────────────┐
│                   قواعد العمل                        │
├─────────────────────────────────────────────────────┤
│ • بداية الدوام: 09:00 صباحاً                        │
│ • نهاية الدوام: 17:00 مساءً                         │
│ • بداية حساب التأخير: من 09:00 مباشرة               │
│ • فترة السماح للانصراف: 17:00 → 17:30               │
│ • بداية حساب Overtime: بعد 17:30                     │
│ • مثال OT: خرج 18:30 = ساعة Overtime                │
│ • الإجازة الأسبوعية: يومين (الجمعة + يوم آخر)       │
│ • الجمعة لا تظهر في ملف Excel                       │
│ • الأسبوع في الملف = 6 أيام (بهم يوم إجازة واحد)   │
│ • يوم الإجازة الثاني: أي يوم بالأسبوع عدا الجمعة   │
│ • الإجازات الرسمية (أعياد): تُدخل بعد رفع الملف    │
│ • لو Clock In فقط → Clock Out الافتراضي = 17:00     │
│ • لو Clock Out فقط → Clock In الافتراضي = 09:00     │
│ • الإعدادات (OT، مواعيد) قابلة للتجاوز عند كل رفع  │
└─────────────────────────────────────────────────────┘
```

---

## 2. 🏗️ Architecture مقترحة للنظام

### 2.1 النمط المعماري: Modular Monolith

```
┌──────────────────────────────────────────────────────────────────┐
│                        Presentation Layer                        │
│                    (Blade Views + Livewire)                       │
├──────────────────────────────────────────────────────────────────┤
│                        Application Layer                         │
│                  (Controllers + Form Requests)                   │
├───────────┬──────────┬───────────┬──────────┬───────────────────┤
│ Employee  │ Attend-  │ Payroll   │ Import   │    Settings       │
│ Module    │ ance     │ Module    │ Module   │    Module         │
│           │ Module   │           │          │                   │
├───────────┴──────────┴───────────┴──────────┴───────────────────┤
│                        Domain Layer                              │
│              (Models + Services + Repositories)                  │
├──────────────────────────────────────────────────────────────────┤
│                     Infrastructure Layer                         │
│           (Database + File Storage + Excel Parser)               │
└──────────────────────────────────────────────────────────────────┘
```

### 2.2 لماذا Modular Monolith؟

- **بسيط ومناسب للحجم الحالي:** لا يحتاج بنية معقدة مثل Microservices.
- **قابل لإضافة ميزات:** كل Module مستقل ويمكن توسيعه بسهولة.
- **كود نظيف:** فصل المسؤوليات يجعل الصيانة والتطوير أسهل.
- **مناسب لـ Laravel 10:** يستغل كل إمكانيات الإطار بدون تعقيد زائد.

### 2.3 Design Patterns المستخدمة

| Pattern | الاستخدام |
|---------|-----------|
| **Service Pattern** | تغليف العمليات المعقدة في كلاسات مستقلة (حساب التأخير، الراتب، الاستيراد) |
| **DTO (Data Transfer Object)** | لنقل بيانات Excel المُحللة بشكل منظم بين الـ Services |
| **Form Request** | التحقق من صحة المدخلات قبل الوصول للـ Controller |

### 2.4 مخطط تدفق البيانات (Data Flow)

```
┌─────────┐     ┌──────────┐     ┌──────────────┐     ┌─────────────┐
│  رفع    │────>│ التحقق   │────>│  قراءة Excel │────>│ تحليل       │
│  الملف  │     │ من الملف │     │  وتحويل     │     │ البيانات    │
└─────────┘     └──────────┘     │  إلى DTO     │     └──────┬──────┘
                                 └──────────────┘            │
                                                              ▼
┌─────────────┐     ┌──────────────┐     ┌──────────────────────────┐
│  عرض       │<────│  حساب        │<────│  تخزين في قاعدة         │
│  النتائج   │     │  المرتبات    │     │  البيانات                │
└─────────────┘     └──────────────┘     └──────────────────────────┘
```

---

## 3. 🗄️ Database Schema

### 3.1 مخطط العلاقات (ERD)

```
┌───────────────────┐       ┌──────────────────┐
│    employees      │       │  attendance_     │
│                   │1─────M│  records         │
├───────────────────┤       ├──────────────────┤
│ id                │       │ id               │
│ ac_no             │       │ employee_id (FK) │
│ name              │       │ date             │
│ basic_salary      │       │ clock_in         │
│ day_off           │       │ clock_out        │
│ is_active         │       │ is_absent        │
│ created_at        │       │ late_minutes     │
│ updated_at        │       │ overtime_minutes │
│ deleted_at        │       │ work_minutes     │
└───────────────────┘       │ notes            │
        │                   │ import_batch_id  │
        │ 1                 │ created_at       │
        │                   │ updated_at       │
        ▼ M                 └──────────────────┘
┌───────────────────┐               │ M
│  payroll_reports  │               ▼ 1
├───────────────────┤       ┌──────────────────┐
│ id                │       │  import_batches  │
│ employee_id (FK)  │       ├──────────────────┤
│ month             │       │ id               │
│ year              │       │ file_name        │
│ total_present_days│       │ month            │
│ total_absent_days │       │ year             │
│ total_late_minutes│       │ status           │
│ total_ot_minutes  │       │ records_count    │
│ basic_salary      │       │ import_settings  │
│ late_deduction    │       │ uploaded_by (FK) │
│ absent_deduction  │       │ created_at       │
│ overtime_bonus    │       └──────────────────┘
│ net_salary        │
│ is_locked         │       ┌──────────────────┐
│ created_at        │       │    settings      │
│ updated_at        │       ├──────────────────┤
└───────────────────┘       │ id               │
                            │ key              │
                            │ value            │
                            │ group            │
                            │ created_at       │
                            │ updated_at       │
                            └──────────────────┘

                            ┌────────────────────────┐
                            │   public_holidays    │
                            ├────────────────────────┤
                            │ id                   │
                            │ import_batch_id (FK) │
                            │ date                 │
                            │ name                 │
                            │ created_at           │
                            │ updated_at           │
                            └────────────────────────┘
```

### 3.2 تفاصيل الجداول

#### جدول `employees`

| العمود | النوع | الوصف |
|--------|-------|-------|
| id | BIGINT UNSIGNED, PK, AI | المعرف الداخلي |
| ac_no | VARCHAR(50) | رقم الموظف في جهاز البصمة |
| name | VARCHAR(255) | اسم الموظف |
| basic_salary | DECIMAL(10,2), DEFAULT 0 | المرتب الأساسي |
| day_off | TINYINT, NULLABLE | يوم الإجازة الثاني (0=أحد, 1=اثنين, 2=ثلاثاء, 3=أربعاء, 4=خميس, 6=سبت) |
| is_active | BOOLEAN, DEFAULT true | هل الموظف نشط |
| created_at | TIMESTAMP | تاريخ الإنشاء |
| updated_at | TIMESTAMP | تاريخ التحديث |
| deleted_at | TIMESTAMP, NULLABLE | Soft Delete |

**الفهارس:**
- `UNIQUE INDEX (ac_no)` — لمنع تكرار رقم الموظف

#### جدول `attendance_records`

| العمود | النوع | الوصف |
|--------|-------|-------|
| id | BIGINT UNSIGNED, PK, AI | المعرف |
| employee_id | BIGINT UNSIGNED, FK | معرف الموظف |
| date | DATE | تاريخ اليوم |
| clock_in | TIME, NULLABLE | وقت الحضور |
| clock_out | TIME, NULLABLE | وقت الانصراف |
| is_absent | BOOLEAN, DEFAULT false | هل هو غياب |
| late_minutes | INT UNSIGNED, DEFAULT 0 | دقائق التأخير |
| overtime_minutes | INT UNSIGNED, DEFAULT 0 | دقائق الـ Overtime |
| work_minutes | INT UNSIGNED, DEFAULT 0 | إجمالي دقائق العمل |
| notes | VARCHAR(500), NULLABLE | ملاحظات |
| import_batch_id | BIGINT UNSIGNED, FK | دفعة الاستيراد |
| created_at | TIMESTAMP | تاريخ الإنشاء |
| updated_at | TIMESTAMP | تاريخ التحديث |

**الفهارس:**
- `UNIQUE INDEX (employee_id, date)` — لمنع تكرار سجل الحضور ليوم واحد
- `INDEX (date)` — للبحث السريع بالتاريخ
- `INDEX (import_batch_id)` — للربط بدفعة الاستيراد

> ⚠️ **Hard Delete:** هذا الجدول **لا يدعم Soft Delete** — حذف بيانات شهر كامل يكون **حذف حقيقي من قاعدة البيانات** (DELETE مباشر). لا يوجد عمود `deleted_at` في هذا الجدول.

#### جدول `import_batches`

| العمود | النوع | الوصف |
|--------|-------|-------|
| id | BIGINT UNSIGNED, PK, AI | المعرف |
| file_name | VARCHAR(255) | اسم الملف الأصلي |
| file_path | VARCHAR(500) | مسار الملف المخزن |
| month | TINYINT | الشهر (1-12) |
| year | SMALLINT | السنة |
| status | ENUM('pending','processing','completed','failed') | حالة المعالجة |
| records_count | INT UNSIGNED, DEFAULT 0 | عدد السجلات |
| employees_count | INT UNSIGNED, DEFAULT 0 | عدد الموظفين |
| error_log | TEXT, NULLABLE | سجل الأخطاء |
| import_settings | JSON, NULLABLE | إعدادات مخصصة لهذا الشهر (تجاوز الإعدادات الافتراضية) |
| uploaded_by | BIGINT UNSIGNED, FK | المستخدم الذي رفع الملف |
| created_at | TIMESTAMP | تاريخ الإنشاء |
| updated_at | TIMESTAMP | تاريخ التحديث |

**الفهارس:**
- `UNIQUE INDEX (month, year)` — لمنع تكرار رفع نفس الشهر
- `INDEX (status)` — للبحث بالحالة

#### جدول `payroll_reports`

| العمود | النوع | الوصف |
|--------|-------|-------|
| id | BIGINT UNSIGNED, PK, AI | المعرف |
| employee_id | BIGINT UNSIGNED, FK | معرف الموظف |
| month | TINYINT | الشهر |
| year | SMALLINT | السنة |
| total_working_days | INT UNSIGNED | إجمالي أيام العمل المفترضة |
| total_present_days | INT UNSIGNED | أيام الحضور |
| total_absent_days | INT UNSIGNED | أيام الغياب |
| total_late_minutes | INT UNSIGNED | إجمالي دقائق التأخير |
| total_overtime_minutes | INT UNSIGNED | إجمالي دقائق Overtime |
| basic_salary | DECIMAL(10,2) | المرتب الأساسي |
| late_deduction | DECIMAL(10,2) | خصم التأخير |
| absent_deduction | DECIMAL(10,2) | خصم الغياب |
| overtime_bonus | DECIMAL(10,2) | مكافأة Overtime |
| net_salary | DECIMAL(10,2) | المرتب النهائي |
| is_locked | BOOLEAN, DEFAULT false | تأمين الراتب (لمنع التعديل) |
| created_at | TIMESTAMP | تاريخ الإنشاء |
| updated_at | TIMESTAMP | تاريخ التحديث |

**الفهارس:**
- `UNIQUE INDEX (employee_id, month, year)` — لمنع تكرار كشف الراتب

#### جدول `settings`

| العمود | النوع | الوصف |
|--------|-------|-------|
| id | BIGINT UNSIGNED, PK, AI | المعرف |
| key | VARCHAR(100) | مفتاح الإعداد |
| value | TEXT | قيمة الإعداد |
| group | VARCHAR(50) | مجموعة الإعداد |
| created_at | TIMESTAMP | تاريخ الإنشاء |
| updated_at | TIMESTAMP | تاريخ التحديث |

**الفهارس:**
- `UNIQUE INDEX (key)` — لمنع تكرار المفتاح

**القيم الافتراضية للإعدادات:**

| المفتاح (key) | المجموعة (group) | القيمة الافتراضية | الوصف |
|---------------|-------------------|-------------------|-------|
| `work_start_time` | work_schedule | `09:00` | بداية الدوام |
| `work_end_time` | work_schedule | `17:00` | نهاية الدوام الرسمي |
| `overtime_start_time` | work_schedule | `17:30` | بداية حساب Overtime (بعد فترة السماح 17:00←17:30) |
| `late_deduction_per_hour` | payroll | `0` | قيمة خصم ساعة التأخير |
| `overtime_rate_per_hour` | payroll | `0` | قيمة ساعة Overtime |
| `absent_deduction_per_day` | payroll | `0` | قيمة خصم يوم الغياب |

#### جدول `public_holidays` (الإجازات الرسمية)

| العمود | النوع | الوصف |
|--------|-------|-------|
| id | BIGINT UNSIGNED, PK, AI | المعرف |
| import_batch_id | BIGINT UNSIGNED, FK | ربط الإجازة بدفعة استيراد الشهر |
| date | DATE | تاريخ الإجازة الرسمية |
| name | VARCHAR(255) | اسم الإجازة (عيد الفطر، عيد الأضحى، ...) |
| created_at | TIMESTAMP | تاريخ الإنشاء |
| updated_at | TIMESTAMP | تاريخ التحديث |

**ملاحظات:**
- لا يدعم Soft Delete — الحذف دائم (حذف حقيقي)
- تُدخل بعد رفع ملف Excel لكل شهر
- يوم الإجازة لا يُحسب غياباً ولا يُحسب خصماً

**الفهارس:**
- `INDEX (import_batch_id)` — للبحث بالدفعة
- `INDEX (date)` — للبحث بالتاريخ
- `UNIQUE INDEX (import_batch_id, date)` — لمنع تكرار نفس التاريخ في نفس الدفعة

#### جدول `users`

| العمود | النوع | الوصف |
|--------|-------|-------|
| id | BIGINT UNSIGNED, PK, AI | المعرف |
| name | VARCHAR(255) | الاسم |
| email | VARCHAR(255), UNIQUE | البريد الإلكتروني |
| password | VARCHAR(255) | كلمة المرور |
| role | ENUM('admin','viewer') | الدور |
| created_at | TIMESTAMP | تاريخ الإنشاء |
| updated_at | TIMESTAMP | تاريخ التحديث |

---

## 4. 📦 تصميم Modules للنظام

### 4.1 نظرة عامة على الوحدات

```
┌─────────────────────────────────────────────────────────────┐
│                     النظام الرئيسي                           │
├──────────┬──────────┬──────────┬──────────┬─────────────────┤
│   Auth   │ Employee │  Import  │ Attend-  │    Payroll      │
│  Module  │  Module  │  Module  │  ance    │    Module       │
│          │          │          │  Module  │                │
├──────────┼──────────┼──────────┼──────────┼─────────────────┤
│ Settings │ Dashboard│ Report   │          │                │
│ Module   │ Module   │ Module   │          │                │
└──────────┴──────────┴──────────┴──────────┴─────────────────┘
```

### 4.2 تفاصيل كل Module

---

#### 🔐 Module 1: Auth (المصادقة)

**المسؤولية:** تسجيل الدخول، إدارة المستخدمين، الصلاحيات.

| المكون | التفاصيل |
|--------|----------|
| **Controller** | `AuthController` |
| **Middleware** | `AuthenticateMiddleware`, `RoleMiddleware` |
| **Views** | `login.blade.php` |
| **الوظائف** | تسجيل دخول، تسجيل خروج، التحقق من الصلاحيات |

**الملاحظات:**
- استخدام Laravel Breeze للبساطة (لا يتطلب PHP أعلى من 8.1)
- دورين فقط: **admin** (صلاحيات كاملة) و **viewer** (عرض فقط)
- صفحة التسجيل مغلقة (Admin ينشئ المستخدمين من الـ Seeder)

---

#### 👥 Module 2: Employee (إدارة الموظفين)

**المسؤولية:** إدارة بيانات الموظفين الأساسية.

| المكون | التفاصيل |
|--------|----------|
| **Model** | `Employee` |
| **Controller** | `EmployeeController` |
| **Service** | `EmployeeService` |
| **Form Request** | `StoreEmployeeRequest`, `UpdateEmployeeRequest` |
| **Views** | `index`, `create`, `edit`, `show` |

**الوظائف الأساسية:**
- عرض قائمة الموظفين (مع بحث وفلترة وتقسيم صفحات)
- إضافة موظف يدوياً
- تعديل بيانات الموظف (المرتب، يوم الإجازة)
- عرض تفاصيل الموظف مع سجل الحضور
- تعطيل/تفعيل الموظف (Soft Delete)
- إنشاء الموظفين تلقائياً من ملف Excel عند أول استيراد

---

#### 📤 Module 3: Import (استيراد الملفات)

**المسؤولية:** رفع ملفات Excel، قراءتها، تحويلها، وتخزينها.

| المكون | التفاصيل |
|--------|----------|
| **Controller** | `ImportController` |
| **Service** | `ExcelImportService`, `AttendanceParserService` |
| **DTO** | `AttendanceRowDTO`, `EmployeeAttendanceDTO` |
| **Form Request** | `UploadFileRequest` |
| **Views** | `upload.blade.php` |

**تدفق العمل:**
```
رفع الملف → التحقق من الامتداد والحجم → حفظ في Storage
    → التحقق من وجود بيانات مكررة (وعرض تحذير إن وجدت)
    → قراءة Excel صف بصف
    → تحويل كل صف إلى DTO
    → تجميع البيانات حسب الموظف
    → حساب التأخير والـ Overtime لكل يوم
    → حفظ في قاعدة البيانات (داخل Transaction)
    → تسجيل Import Batch (completed)
```

**التعامل مع التكرار:**
- عند رفع ملف لشهر موجود مسبقاً:
  1. عرض تحذير للمستخدم
  2. إعطاء خيارين: **تجاهل** أو **استبدال البيانات القديمة**
  3. عند الاستبدال: حذف سجلات الشهر القديمة ثم إدخال الجديدة (داخل Transaction)

---

#### 📅 Module 4: Attendance (الحضور والانصراف)

**المسؤولية:** حساب وتحليل بيانات الحضور.

| المكون | التفاصيل |
|--------|----------|
| **Model** | `AttendanceRecord` |
| **Controller** | `AttendanceController` |
| **Service** | `AttendanceCalculationService` |
| **Views** | `report.blade.php`, `employee-details.blade.php` |

**الوظائف:**
- حساب التأخير لكل يوم
- حساب Overtime لكل يوم
- حساب دقائق العمل الفعلية
- تحديد أيام الغياب
- عرض تقرير شهري لموظف
- عرض تقرير شهري لكل الموظفين

---

#### 💰 Module 5: Payroll (المرتبات)

**المسؤولية:** حساب المرتبات الشهرية.

| المكون | التفاصيل |
|--------|----------|
| **Model** | `PayrollReport` |
| **Controller** | `PayrollController` |
| **Service** | `PayrollCalculationService` |
| **Form Request** | `CalculatePayrollRequest` |
| **Views** | `index.blade.php`, `calculate.blade.php` |

**الوظائف:**
- إدخال قيم الحساب (خصم تأخير، خصم غياب، overtime)
- حساب الراتب لموظف واحد
- حساب الرواتب لجميع الموظفين (Bulk)
- عرض كشف المرتبات الشهري
- تصدير كشف المرتبات إلى Excel/PDF
- تأمين كشف الراتب (Lock) لمنع إعادة الحساب

---

#### 📊 Module 6: Dashboard (لوحة التحكم)

**المسؤولية:** عرض ملخص البيانات والإحصائيات.

| المكون | التفاصيل |
|--------|----------|
| **Controller** | `DashboardController` |
| **Service** | `DashboardStatisticsService` |
| **Views** | `dashboard.blade.php` مع مكونات Livewire |

**البيانات المعروضة:**
- إجمالي عدد الموظفين
- متوسط نسبة الحضور للشهر الحالي
- إجمالي دقائق التأخير هذا الشهر
- إجمالي ساعات Overtime هذا الشهر
- أكثر 5 موظفين تأخيراً
- أكثر 5 موظفين Overtime
- رسم بياني للحضور اليومي
- حالة آخر عملية استيراد
- إجمالي المرتبات المحسوبة

---

#### ⚙️ Module 7: Settings (الإعدادات)

**المسؤولية:** إدارة إعدادات النظام.

| المكون | التفاصيل |
|--------|----------|
| **Model** | `Setting` |
| **Controller** | `SettingController` |
| **Service** | `SettingService` (مع Caching) |
| **Form Request** | `UpdateSettingsRequest` |
| **Views** | `settings.blade.php` |

**الإعدادات:**
- مواعيد العمل (بداية ونهاية)
- وقت بداية حساب Overtime (افتراضي: 17:30 — قابل للتغيير هنا أو عند كل رفع)
- قيم الخصم والإضافة (تأخير، غياب، Overtime)

---

## 5. 📄 كيفية قراءة ملف Excel

### 5.1 المكتبة المستخدمة

**Maatwebsite/Laravel-Excel (الإصدار 3.1)** — متوافق مع PHP 8.1 و Laravel 10.

```
composer require maatwebsite/excel:^3.1
```

### 5.2 تحليل هيكل ملف Excel

```
┌────────────────────────────────────────────────────────────────────────────┐
│                              ملف Excel                                     │
├────────┬────────┬────────────┬──────────┬───────────┬──────┬──────────────┤
│ AC-No  │ Name   │ Date       │ Clock In │ Clock Out │ Late │ ... (تُهمل)  │
│  (A)   │  (B)   │   (C)      │   (D)    │    (E)    │  (F) │              │
├────────┼────────┼────────────┼──────────┼───────────┼──────┼──────────────┤
│   1    │ Ahmed  │ 2026-02-01 │  09:00   │  17:00    │      │              │
│   1    │ Ahmed  │ 2026-02-02 │  09:40   │  15:10    │      │              │
│   1    │ Ahmed  │ 2026-02-03 │          │           │      │  Absent      │
│  ...   │  ...   │    ...     │   ...    │   ...     │ ...  │              │
│   2    │ Sara   │ 2026-02-01 │  08:55   │  18:30    │      │              │
│   2    │ Sara   │ 2026-02-02 │  09:15   │  17:00    │      │              │
│  ...   │  ...   │    ...     │   ...    │   ...     │ ...  │              │
└────────┴────────┴────────────┴──────────┴───────────┴──────┴──────────────┘
```

### 5.3 خطوات القراءة

```
الخطوة 1: التحقق من الملف
├── التحقق من الامتداد (.xlsx, .xls)
├── التحقق من الحجم (Max: 10MB)
└── التحقق من وجود الأعمدة المطلوبة (A, B, C, D, E)

الخطوة 2: قراءة الملف
├── قراءة جميع الصفوف
├── تجاهل صف العناوين (Header Row)
├── تجاهل الصفوف الفارغة
└── الاعتماد فقط على الأعمدة: A (AC-No), B (Name), C (Date), D (Clock In), E (Clock Out)

الخطوة 3: التجميع حسب الموظف
├── تجميع الصفوف حسب AC-No
├── لكل موظف: مصفوفة من الأيام
└── التحقق من اكتمال البيانات

الخطوة 4: التحويل إلى DTOs
├── AttendanceRowDTO لكل صف
│   ├── ac_no: string
│   ├── name: string
│   ├── date: Carbon
│   ├── clock_in: ?Carbon
│   ├── clock_out: ?Carbon
│   └── is_absent: bool
│
└── EmployeeAttendanceDTO لكل موظف
    ├── ac_no: string
    ├── name: string
    └── records: Collection<AttendanceRowDTO>

الخطوة 5: تحديد الشهر والسنة
├── استخراج الشهر والسنة من أول تاريخ في الملف
├── التحقق من أن جميع التواريخ في نفس الشهر
└── إذا كان هناك أكثر من شهر: رفض الملف مع رسالة خطأ
```

### 5.4 التعامل مع تنسيقات الوقت

```
الحالات المحتملة لعمود Clock In / Clock Out:
├── "09:40"         → تحويل إلى Carbon::createFromTimeString('09:40')
├── "9:40"          → تحويل مع إضافة صفر بادئ
├── "09:40:00"      → تحويل مع تجاهل الثواني
├── Excel Time      → تحويل من رقم عشري Excel إلى وقت
│   (مثل 0.4027)     (Excel يخزن الوقت كنسبة من 24 ساعة)
├── Clock In فارغ فقط   → يُستخدم 09:00 كقيمة افتراضية + ملاحظة
├── Clock Out فارغ فقط  → يُستخدم 17:00 كقيمة افتراضية + ملاحظة
├── كلاهما فارغ       → غياب في هذا اليوم
└── نص غير صالح     → تسجيل خطأ في السجل + تجاهل الصف
```

### 5.5 تحسين الأداء عند القراءة

- استخدام **Database Transactions** لضمان تكامل البيانات (إما تكتمل بالكامل أو ترتجع)
- استخدام **Batch Insert** بدلاً من إدراج صف بصف
- المعالجة متزامنة (Synchronous): ملف 20 موظف ينتهي في أقل من 3 ثواني، لا حاجة لـ Queue

---

## 6. ⏰ خوارزمية حساب الحضور والتأخير

### 6.1 المتغيرات الأساسية

```
الثوابت (من الإعدادات أو من import_settings لهذه الدفعة):
├── WORK_START    = 09:00        (بداية الدوام الرسمي)
├── WORK_END      = 17:00        (نهاية الدوام الرسمي)
├── OT_START      = 17:30        (بداية حساب الـ Overtime - بعد فترة السماح)
├── FRIDAY        = 5            (يوم الجمعة - لا يظهر في الملف)
└── DAY_OFF       = يوم الإجازة الثاني للموظف (من بيانات الموظف)

ℹ️ فترة السماح: 17:00 → 17:30 = لا خصم انصراف مبكر ولا OT
ℹ️ OT_START قابل للتجاوز لكل شهر عبر Dynamic Settings عند رفع الملف
```

### 6.2 خوارزمية حساب يوم واحد

```
Function: calculateDayAttendance(record, employee, settings, publicHolidays)

المدخلات:
  - record: { date, clock_in, clock_out }
  - employee: { day_off }
  - settings: { work_start, work_end, ot_start }   ← من import_settings أو الإعدادات الافتراضية
  - publicHolidays: مصفوفة تواريخ الإجازات الرسمية لهذا الشهر

المخرجات:
  - { late_minutes, overtime_minutes, work_minutes, is_absent, notes }

═══════════════════════════════════════════════════════

الخطوة 0: التحقق هل اليوم إجازة رسمية (عيد)
┌─────────────────────────────────────────┐
│ IF record.date IN publicHolidays        │
│   → is_absent = false (ليس غياباً)      │
│   → late_minutes = 0                    │
│   → overtime_minutes = 0               │
│   → work_minutes = 0                   │
│   → notes = "إجازة رسمية"             │
│   → RETURN  ← لا يكمل باقي الخطوات    │
└─────────────────────────────────────────┘

الخطوة 1: التحقق هل اليوم إجازة
┌─────────────────────────────────────────┐
│ IF record.date هو يوم الجمعة           │
│   → تجاهل (لن يظهر أصلاً في الملف)    │
│                                         │
│ IF record.date.dayOfWeek == DAY_OFF     │
│   → هذا يوم إجازة رسمي                 │
│   → IF clock_in موجود                   │
│     → حساب OT كامل (عمل في إجازة) │
│   → ELSE                                │
│     → is_absent = false (إجازة رسمية)   │
│   → RETURN                              │
└─────────────────────────────────────────┘

الخطوة 2: التحقق من الغياب
┌─────────────────────────────────────────┐
│ IF clock_in == null AND clock_out == null│
│   → is_absent = true                    │
│   → late_minutes = 0                    │
│   → overtime_minutes = 0               │
│   → work_minutes = 0                   │
│   → RETURN                              │
└─────────────────────────────────────────┘

الخطوة 3: تطبيق القيم الافتراضية
┌─────────────────────────────────────────┐
│ IF clock_in == null AND clock_out موجود │
│   → clock_in = 09:00 (قيمة افتراضية)  │
│   → إضافة note = "حضور بدون بصمة"   │
│                                         │
│ IF clock_out == null AND clock_in موجود │
│   → clock_out = 17:00 (قيمة افتراضية) │
│   → إضافة note = "انصراف بدون بصمة"  │
└─────────────────────────────────────────┘

الخطوة 4: حساب التأخير
┌─────────────────────────────────────────┐
│ IF clock_in > WORK_START                │
│   → late_minutes = clock_in - WORK_START│
│   → (بالدقائق)                          │
│ ELSE                                    │
│   → late_minutes = 0                    │
│   (الحضور المبكر لا يُخصم من التأخير)  │
└─────────────────────────────────────────┘

الخطوة 5: حساب Overtime
┌─────────────────────────────────────────┐
│ ⚡ فترة السماح: 17:00 → 17:30           │
│    لا خصم ولا OT في هذه الفترة         │
│                                         │
│ IF clock_out > OT_START (17:30)         │
│   → overtime_minutes = clock_out -      │
│     OT_START (بالدقائق)                 │
│   → مثال: خرج 18:30 → OT = 60 دقيقة    │
│   → مثال: خرج 17:20 → OT = 0 (سماح)   │
│ ELSE                                    │
│   → overtime_minutes = 0                │
└─────────────────────────────────────────┘

الخطوة 6: حساب ساعات العمل الفعلية
┌─────────────────────────────────────────┐
│ work_minutes = clock_out - clock_in     │
│ (بعد تطبيق القيم الافتراضية إن لزم)   │
└─────────────────────────────────────────┘
```

### 6.3 خوارزمية حساب أيام الغياب الشهرية

```
Function: calculateMonthlyAbsence(employee, month, year, attendanceRecords, publicHolidays)

الخطوة 1: تحديد أيام العمل المفترضة في الشهر
┌──────────────────────────────────────────────────────┐
│ allDaysInMonth = جميع أيام الشهر                     │
│ workingDays = []                                     │
│                                                      │
│ FOR EACH day IN allDaysInMonth:                      │
│   IF day.dayOfWeek != FRIDAY (5)                     │
│   AND day.dayOfWeek != employee.day_off              │
│   AND day NOT IN publicHolidays  ← استثناء الأعياد  │
│     → workingDays.push(day)                          │
│                                                      │
│ totalWorkingDays = workingDays.count()               │
│ ℹ️ الإجازات الرسمية لا تُحسب ضمن أيام العمل        │
└──────────────────────────────────────────────────────┘

الخطوة 2: مقارنة أيام العمل بسجلات الحضور
┌──────────────────────────────────────────────────────┐
│ presentDays = 0                                      │
│ absentDays = 0                                       │
│                                                      │
│ FOR EACH day IN workingDays:                         │
│   record = attendanceRecords.find(date == day)       │
│                                                      │
│   IF record == null                                  │
│     → absentDays++ (لا يوجد سجل في الملف)           │
│                                                      │
│   ELSE IF record.clock_in == null                    │
│     AND record.clock_out == null                     │
│     → absentDays++ (موجود في الملف لكن بدون بصمة)   │
│                                                      │
│   ELSE                                               │
│     → presentDays++                                  │
│                                                      │
│ ⚠️ ملاحظة مهمة:                                     │
│ الأيام التي لا تظهر في ملف Excel ولكنها أيام عمل    │
│ يجب أن تُعتبر غياب أيضاً                            │
└──────────────────────────────────────────────────────┘
```

### 6.4 مثال عملي

```
═══════════════════ مثال ═══════════════════

الموظف: أحمد (AC-No: 1)
الشهر: فبراير 2026
يوم الإجازة الثاني: السبت

بيانات من Excel:
┌──────────────┬──────────┬───────────┐
│ التاريخ      │ Clock In │ Clock Out │
├──────────────┼──────────┼───────────┤
│ 2026-02-01   │  09:00   │  17:00    │  → حضور عادي, لا تأخير, لا OT
│ 2026-02-02   │  09:40   │  15:10    │  → تأخير 40 د, لا OT
│ 2026-02-03   │  08:50   │  18:45    │  → لا تأخير, OT = 75 دقيقة (18:45-17:30)
│ 2026-02-04   │  10:00   │  17:00    │  → تأخير 60 د, لا OT
│ 2026-02-05   │  09:30   │  (فارغ)   │  → تأخير 30 د, clock_out=17:00 (افتراضي), لا OT
│ 2026-02-06   │  (الجمعة - لا يظهر)  │  → إجازة
│ 2026-02-07   │  (السبت) │           │  → إجازة رسمية (day_off)
│ 2026-02-08   │  (فارغ)  │  (فارغ)   │  → غياب
│ ...          │   ...    │   ...     │
└──────────────┴──────────┴───────────┘

النتيجة:
├── أيام الحضور: 4 (تشمل يوم القيمة الافتراضية)
├── أيام الغياب: 1
├── إجمالي التأخير: 130 دقيقة (40+60+30)
└── إجمالي Overtime: 75 دقيقة (بعد تطبيق OT_START=17:30)
```

---

## 7. 💰 خوارزمية حساب المرتبات

### 7.1 المعادلة الأساسية

```
╔══════════════════════════════════════════════════════════════╗
║                                                              ║
║  المرتب النهائي = المرتب الأساسي                             ║
║                   - خصم التأخير                              ║
║                   - خصم الغياب                               ║
║                   + مكافأة Overtime                           ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
```

### 7.2 تفاصيل الحساب

```
Function: calculateSalary(employee, month, year, settings)

═══════════════════════════════════════════════════

المدخلات من المستخدم (أو من الإعدادات):
├── basic_salary          = المرتب الأساسي للموظف
├── late_deduction_rate   = قيمة خصم الساعة تأخير (بالجنيه)
├── overtime_rate          = قيمة ساعة Overtime (بالجنيه)
└── absent_deduction_rate = قيمة خصم يوم الغياب (بالجنيه)

═══════════════════════════════════════════════════

الخطوة 1: جمع البيانات من سجلات الحضور
┌──────────────────────────────────────────────────┐
│ monthRecords = getAttendanceRecords(              │
│                  employee, month, year)           │
│                                                  │
│ totalLateMinutes = SUM(record.late_minutes)      │
│ totalOTMinutes   = SUM(record.overtime_minutes)  │
│ totalAbsentDays  = COUNT(absent records)          │
│ totalPresentDays = COUNT(present records)         │
└──────────────────────────────────────────────────┘

الخطوة 2: تحويل الدقائق إلى ساعات (للحساب المالي)
┌──────────────────────────────────────────────────┐
│ totalLateHours = totalLateMinutes / 60           │
│ totalOTHours   = totalOTMinutes / 60             │
│                                                  │
│ ⚠️ يتم الحساب بالدقائق بدقة ثم التحويل          │
│    مثال: 100 دقيقة = 1.667 ساعة                 │
└──────────────────────────────────────────────────┘

الخطوة 3: حساب الخصومات والإضافات
┌──────────────────────────────────────────────────┐
│ lateDeduction  = totalLateHours                  │
│                  × late_deduction_rate            │
│                                                  │
│ absentDeduction = totalAbsentDays                │
│                   × absent_deduction_rate         │
│                                                  │
│ overtimeBonus   = totalOTHours                   │
│                   × overtime_rate                 │
└──────────────────────────────────────────────────┘

الخطوة 4: حساب المرتب النهائي
┌──────────────────────────────────────────────────┐
│ netSalary = basic_salary                         │
│             - lateDeduction                      │
│             - absentDeduction                    │
│             + overtimeBonus                      │
│                                                  │
│ IF netSalary < 0                                 │
│   → netSalary = 0 (لا يمكن أن يكون بالسالب)     │
│   → تسجيل تحذير                                 │
└──────────────────────────────────────────────────┘
```

### 7.3 مثال عملي للحساب

```
═══════════════════ مثال ═══════════════════

الموظف: أحمد
المرتب الأساسي: 5000 جنيه

إعدادات الحساب:
├── خصم ساعة التأخير: 50 جنيه
├── قيمة ساعة Overtime: 40 جنيه
└── خصم يوم الغياب: 200 جنيه

بيانات الشهر:
├── إجمالي دقائق التأخير: 300 دقيقة (5 ساعات)
├── إجمالي دقائق Overtime: 480 دقيقة (8 ساعات)
└── أيام الغياب: 2 يوم

الحساب:
├── خصم التأخير   = 5 × 50  = 250 جنيه
├── خصم الغياب    = 2 × 200 = 400 جنيه
├── مكافأة OT     = 8 × 40  = 320 جنيه
│
├── إجمالي الخصم  = 250 + 400 = 650 جنيه
├── إجمالي الإضافة = 320 جنيه
│
└── المرتب النهائي = 5000 - 650 + 320 = 4,670 جنيه
```

---

## 8. 🗂️ هيكل المشروع داخل Laravel

```
attendance-system/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── RecalculatePayrollCommand.php    # أمر إعادة حساب الرواتب
│   │
│   ├── DTOs/                                     # Data Transfer Objects
│   │   ├── AttendanceRowDTO.php
│   │   └── EmployeeAttendanceDTO.php
│   │
│   ├── Enums/                                    # PHP 8.1 Enums
│   │   ├── ImportStatus.php                      # pending, completed, failed
│   │   └── DayOfWeek.php                         # أيام الأسبوع
│   │
│   ├── Exceptions/
│   │   ├── DuplicateImportException.php
│   │   └── InvalidExcelFormatException.php
│   │
│   ├── Exports/                                  # تصدير Excel
│   │   └── PayrollExport.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── LoginController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── EmployeeController.php
│   │   │   ├── ImportController.php
│   │   │   ├── PublicHolidayController.php      # إدارة الإجازات الرسمية
│   │   │   ├── AttendanceController.php
│   │   │   ├── PayrollController.php
│   │   │   └── SettingController.php
│   │   │
│   │   ├── Middleware/
│   │   │   └── CheckRole.php                    # admin / viewer
│   │   │
│   │   └── Requests/
│   │       ├── UploadFileRequest.php
│   │       ├── StoreEmployeeRequest.php
│   │       ├── UpdateEmployeeRequest.php
│   │       ├── CalculatePayrollRequest.php
│   │       └── UpdateSettingsRequest.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Employee.php
│   │   ├── AttendanceRecord.php             # لا يدعم Soft Delete (حذف حقيقي)
│   │   ├── ImportBatch.php
│   │   ├── PayrollReport.php
│   │   ├── PublicHoliday.php                # الإجازات الرسمية
│   │   └── Setting.php
│   │
│   ├── Services/                                 # Business Logic (كل منطق هنا)
│   │   ├── Excel/
│   │   │   ├── ExcelReaderService.php           # قراءة الملف
│   │   │   ├── ExcelParserService.php           # تحليل البيانات
│   │   │   └── ExcelTimeHelper.php              # تحويل أوقات Excel
│   │   │
│   │   ├── Attendance/
│   │   │   ├── AttendanceCalculationService.php # حساب التأخير والـ OT
│   │   │   ├── AbsenceDetectionService.php      # كشف الغياب
│   │   │   ├── WorkingDaysService.php           # حساب أيام العمل
│   │   │   └── PublicHolidayService.php         # إدارة الإجازات الرسمية
│   │   │
│   │   ├── Payroll/
│   │   │   └── PayrollCalculationService.php    # حساب المرتب
│   │   │
│   │   ├── Import/
│   │   │   └── ImportService.php                # إدارة عملية الاستيراد
│   │   │
│   │   ├── Dashboard/
│   │   │   └── DashboardStatisticsService.php   # إحصائيات Dashboard
│   │   │
│   │   └── Setting/
│   │       └── SettingService.php               # إدارة الإعدادات (مع Cache)
│   │
│   └── Providers/
│       └── AppServiceProvider.php
│
├── config/
│   └── attendance.php                            # إعدادات افتراضية للنظام
│
├── database/
│   ├── factories/
│   │   ├── EmployeeFactory.php
│   │   └── AttendanceRecordFactory.php
│   │
│   ├── migrations/
│   │   ├── 2026_03_01_000001_create_employees_table.php
│   │   ├── 2026_03_01_000002_create_import_batches_table.php
│   │   ├── 2026_03_01_000003_create_attendance_records_table.php
│   │   ├── 2026_03_01_000004_create_payroll_reports_table.php
│   │   ├── 2026_03_01_000005_create_settings_table.php
│   │   └── 2026_03_01_000006_create_public_holidays_table.php
│   │
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── DefaultSettingsSeeder.php
│       └── AdminUserSeeder.php
│
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php                    # Layout رئيسي
│       │   └── sidebar.blade.php                # القائمة الجانبية
│       │
│       ├── auth/
│       │   └── login.blade.php
│       │
│       ├── dashboard/
│       │   ├── index.blade.php
│       │   └── components/                      # مكونات Dashboard
│       │       ├── stats-cards.blade.php
│       │       ├── attendance-chart.blade.php
│       │       ├── top-late-employees.blade.php
│       │       └── recent-imports.blade.php
│       │
│       ├── employees/
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   ├── edit.blade.php
│       │   └── show.blade.php                   # تفاصيل الموظف
│       │
│       ├── import/
│       │   ├── upload.blade.php
│       │   └── confirm.blade.php                # تأكيد الاستيراد + إعدادات الشهر + الإجازات
│       │
│       ├── attendance/
│       │   └── report.blade.php
│       │
│       ├── payroll/
│       │   ├── index.blade.php                  # كشف المرتبات
│       │   └── calculate.blade.php              # حساب المرتبات
│       │
│       ├── settings/
│       │   └── index.blade.php
│       │
│       └── components/                          # Blade Components مشتركة
│           ├── alert.blade.php
│           ├── modal.blade.php
│           ├── data-table.blade.php
│           └── stat-card.blade.php
│
├── routes/
│   └── web.php
│
├── storage/
│   └── app/
│       └── imports/                             # مجلد تخزين ملفات Excel المرفوعة
│
├── tests/
│   ├── Unit/
│   │   ├── Services/
│   │   │   ├── AttendanceCalculationServiceTest.php
│   │   │   ├── PayrollCalculationServiceTest.php
│   │   │   ├── ExcelParserServiceTest.php
│   │   │   └── WorkingDaysServiceTest.php
│   │   │
│   │   └── Models/
│   │       ├── EmployeeTest.php
│   │       └── AttendanceRecordTest.php
│   │
│   └── Feature/
│       ├── ImportTest.php
│       ├── AttendanceReportTest.php
│       ├── PayrollTest.php
│       └── DashboardTest.php
│
├── .env.example
├── composer.json
└── README.md
```

### 8.1 الـ Routes المقترحة

```
المسارات (routes/web.php):
═══════════════════════════════════════════════════════════════

المصادقة:
├── GET    /login                    → Auth\LoginController@showLogin
├── POST   /login                    → Auth\LoginController@login
└── POST   /logout                   → Auth\LoginController@logout

Dashboard:
└── GET    /dashboard                → DashboardController@index

الموظفين:
├── GET    /employees                → EmployeeController@index
├── GET    /employees/create         → EmployeeController@create
├── POST   /employees                → EmployeeController@store
├── GET    /employees/{employee}     → EmployeeController@show
├── GET    /employees/{employee}/edit → EmployeeController@edit
├── PUT    /employees/{employee}     → EmployeeController@update
└── DELETE /employees/{employee}     → EmployeeController@destroy

الاستيراد:
├── GET    /import                          → ImportController@showForm
├── POST   /import/upload                   → ImportController@upload       (رفع وتحليل الملف فقط)
├── GET    /import/{batch}/confirm          → ImportController@showConfirm  (عرض شاشة التأكيد)
├── POST   /import/{batch}/confirm          → ImportController@confirm      (تنفيذ الاستيراد مع الإعدادات)
├── POST   /import/{batch}/holidays         → PublicHolidayController@store   (إضافة إجازة)
├── DELETE /import/{batch}/holidays/{id}  → PublicHolidayController@destroy (حذف إجازة)
├── DELETE /import/{batch}               → ImportController@destroy      (حذف بيانات شهر كامل — Hard Delete)
├── GET    /import/status/{batch}          → ImportController@status
└── GET    /import/history                  → ImportController@history

الحضور:
├── GET    /attendance               → AttendanceController@index
├── GET    /attendance/report        → AttendanceController@report
└── GET    /attendance/employee/{id} → AttendanceController@employeeReport

المرتبات:
├── GET    /payroll                  → PayrollController@index
├── GET    /payroll/calculate        → PayrollController@showCalculateForm
├── POST   /payroll/calculate        → PayrollController@calculate
├── GET    /payroll/report/{month}   → PayrollController@report
├── POST   /payroll/lock/{report}    → PayrollController@lock
└── GET    /payroll/export/{month}   → PayrollController@export

الإعدادات:
├── GET    /settings                 → SettingController@index
└── PUT    /settings                 → SettingController@update
```

---

## 9. 📈 قابلية التوسع وإضافة ميزات جديدة

### 9.1 الفلسفة: بسيط الآن، قابل للنمو

المشروع مصمم لشركة واحدة صغيرة (20 موظف أو أقل). الكود مكتوب بشكل نظيف يسمح بـ:
- **إضافة ميزات جديدة** بدون إعادة هيكلة الكود الموجود
- **التعديل والصيانة** بسهولة من أي مطور لاحقاً
- **توسيع أي وحدة** باستقلالية كاملة

### 9.2 كيف تُضاف ميزات مستقبلاً بسهولة

#### مثال 1: إضافة أنواع إجازات (سنوية، مرضية، طارئة)
```
المطلوب:
├── إنشاء جدول leaves جديد
├── إنشاء LeaveController و LeaveService
├── تعديل AbsenceDetectionService لمراعاة الإجازات
└── لا يتأثر أي كود موجود
```

#### مثال 2: إضافة تقارير PDF
```
المطلوب:
├── composer require barryvdh/laravel-dompdf
├── إضافة method exportPdf في PayrollController
└── لا تعديل على أي Service موجود
```

#### مثال 3: إضافة API للموبايل
```
المطلوب:
├── إنشاء routes/api.php
├── إنشاء Api/Controllers منفصلة
└── استخدام نفس الـ Services الموجودة بدون تكرار الكود
```

#### مثال 4: إضافة الإجازات الرسمية (أعياد)
```
المطلوب:
├── إنشاء جدول public_holidays
├── تعديل WorkingDaysService لاستثناءها من حساب الغياب
└── إضافة صفحة إدارة الإجازات الرسمية
```

### 9.3 لماذا Service Pattern يجعل التوسع سهلاً

```
Controller  →  يستقبل الـ Request ويرسل الـ Response فقط
Service     →  يحتوي كل منطق العمل (قابل للاختبار والتعديل)
Model       →  يتعامل مع قاعدة البيانات فقط

النتيجة:
├── إضافة ميزة جديدة = كتابة Service جديد فقط
├── اختبار الكود = اختبار الـ Service مباشرة بدون HTTP
└── تعديل قاعدة الحساب = تعديل مكان واحد فقط
```

### 9.4 الأداء مع 20 موظف

```
بيانات الشهر لـ 20 موظف:
├── ملف Excel: ~520 صف (20 × 26 يوم عمل)
├── وقت الاستيراد: أقل من 3 ثواني
├── قاعدة البيانات: ~6,240 سجل سنوياً (خفيفة جداً)
└── لا حاجة لـ Queue أو Redis أو Caching معقد

⚠️ لو زاد عدد الموظفين لأكثر من 200:
├── يمكن إضافة Queue Job للاستيراد بتعديل بسيط في ImportService
└── البنية الحالية تدعم ذلك بدون إعادة هيكلة
```

### 9.5 الأمان

```
إجراءات الأمان:
═══════════════════════════════════════════

1. Authentication
   ├── Laravel Breeze
   └── Session-based Authentication

2. Authorization
   ├── Role-based: admin (كامل) / viewer (قراءة فقط)
   └── CheckRole Middleware لحماية جميع الصفحات

3. Data Protection
   ├── CSRF Protection (Laravel default)
   ├── XSS Protection (Blade auto-escaping)
   ├── SQL Injection Protection (Eloquent ORM)
   └── File Upload Validation (نوع وحجم الملف)
```

---

## 10. ⚠️ Edge Cases المحتملة

### 10.1 Edge Cases في ملف Excel

| # | الحالة | الحل المقترح |
|---|--------|-------------|
| EC-01 | الملف فارغ تماماً | عرض رسالة خطأ: "الملف لا يحتوي على بيانات" |
| EC-02 | أعمدة ناقصة أو بترتيب مختلف | التحقق من وجود الأعمدة المطلوبة، محاولة البحث عنها بالاسم |
| EC-03 | صفوف فارغة بين بيانات الموظفين | تجاهل الصفوف الفارغة والاستمرار |
| EC-04 | تنسيق الوقت مختلف (AM/PM بدلاً من 24h) | دعم كلا التنسيقين في Parser |
| EC-05 | الوقت مخزن كرقم عشري في Excel (0.375 = 09:00) | تحويل الرقم العشري إلى وقت |
| EC-06 | التاريخ بتنسيقات مختلفة (DD/MM/YYYY vs YYYY-MM-DD) | استخدام Carbon::parse مع محاولات متعددة |
| EC-07 | نفس الموظف بأسماء مختلفة في ملفات مختلفة | الاعتماد على AC-No كمعرف أساسي، تحديث الاسم من آخر ملف |
| EC-08 | ملف يحتوي على أكثر من شهر | رفض الملف وطلب رفع ملف لكل شهر |
| EC-09 | Clock Out قبل Clock In | تسجيل تحذير، اعتبار اليوم غير صالح |
| EC-10 | Clock In فقط بدون Clock Out | استخدام 17:00 كقيمة افتراضية + إضافة ملاحظة في السجل |
| EC-11 | Clock Out فقط بدون Clock In | استخدام 09:00 كقيمة افتراضية + إضافة ملاحظة في السجل |
| EC-12 | بيانات في Header Row مختلفة عن المتوقع | البحث عن أول صف يحتوي على بيانات رقمية في AC-No |

### 10.2 Edge Cases في حساب الحضور

| # | الحالة | الحل المقترح |
|---|--------|-------------|
| EC-13 | موظف حضر في يوم إجازته | حساب اليوم كـ Overtime كامل |
| EC-14 | موظف جديد لم يعمل الشهر كاملاً | حساب أيام العمل من تاريخ أول حضور |
| EC-15 | موظف ترك العمل في منتصف الشهر | حساب أيام العمل حتى آخر يوم حضور |
| EC-16 | يوم إجازة رسمية (عيد) تم إدخاله في النظام | لا يُعتبر غياباً — late_minutes=0 و OT=0 و يُستثنى من أيام العمل المحسوبة |
| EC-16b | يوم إجازة رسمية لم يُدخل في النظام | يُعتبر غياباً عادياً — المستخدم مسؤول عن إدخال الإجازات |
| EC-16c | إجازة أُدخلت بعد حساب الراتب | يظهر تحذير: "تم إضافة إجازة بعد حساب الراتب، يُنصح بإعادة الحساب" |
| EC-17 | الموظف حضر بعد الساعة 12:00 ظهراً | يُحسب تأخير كبير (يمكن إضافة حد أقصى اختياري لاحقاً) |
| EC-18 | Clock In = 09:00 بالضبط | لا يوجد تأخير |
| EC-19 | Clock Out بين 17:00 و 17:30 | لا يوجد Overtime (فترة السماح) ولا خصم انصراف مبكر |
| EC-19b | Clock Out = 17:30 بالضبط | لا يوجد Overtime (OT يبدأ من 17:31 فما فوق) |
| EC-19c | إعداد وقت OT الديناميكي يختلف عن الافتراضي | يُستخدم القيمة المخصصة لهذا الشهر فقط، ولا تتأثر باقي الشهور |

### 10.3 Edge Cases في حساب المرتب

| # | الحالة | الحل المقترح |
|---|--------|-------------|
| EC-21 | الخصومات أكبر من المرتب الأساسي | المرتب النهائي = 0 (لا يكون بالسالب)، مع تحذير |
| EC-22 | لم يتم رفع ملف الحضور بعد | منع حساب الراتب وعرض رسالة |
| EC-23 | موظف ليس له مرتب أساسي محدد | طلب إدخال المرتب أولاً |
| EC-24 | إعادة حساب الراتب بعد تعديل بيانات الحضور | السماح بإعادة الحساب إذا لم يكن مؤمناً (locked) |
| EC-25 | تعديل إعدادات الخصم بعد حساب الراتب | الراتب المحسوب لا يتأثر (يُحفظ كنسخة)، يمكن إعادة الحساب |

### 10.4 Edge Cases في التكرار والاستيراد

| # | الحالة | الحل المقترح |
|---|--------|-------------|
| EC-26 | رفع نفس الملف مرتين | كشف التكرار بالشهر والسنة، عرض خيار الاستبدال |
| EC-27 | رفع ملف بنفس الشهر لكن ببيانات مختلفة (تحديث) | استبدال البيانات القديمة بالكامل |
| EC-28 | رفع ملف أثناء معالجة ملف آخر | منع الرفع وعرض رسالة "جارِ معالجة ملف" |
| EC-29 | فشل المعالجة في المنتصف | Rollback كل البيانات (Transaction)، تحديث حالة الدفعة إلى failed |
| EC-30 | ملف كبير جداً (أكثر من 10000 صف) | معالجة في الخلفية مع شريط تقدم |

---

## 11. 📋 خطة تنفيذ Step-by-Step

### المرحلة 0: التجهيز والإعداد (يوم واحد)

```
الخطوات:
═══════════════════════════════════════════

□ 0.1  إنشاء مشروع Laravel 10 جديد
       composer create-project laravel/laravel:^10.0 attendance-system

□ 0.2  إعداد قاعدة البيانات MySQL
       - إنشاء قاعدة البيانات
       - تحديث ملف .env

□ 0.3  تثبيت الحزم المطلوبة
       - composer require maatwebsite/excel:^3.1
       - composer require laravel/breeze --dev
       - php artisan breeze:install blade

□ 0.4  إعداد هيكل المجلدات
       - إنشاء مجلدات DTOs, Enums, Services

□ 0.5  إعداد التصميم الأساسي (Layout)
       - تحديث Layout الرئيسي
       - إضافة Sidebar
       - اختيار وتطبيق AdminLTE أو Tailwind Dashboard Template

□ 0.6  إنشاء ملف config/attendance.php
       - القيم الافتراضية للنظام
```

### المرحلة 1: قاعدة البيانات والنماذج (يومين)

```
الخطوات:
═══════════════════════════════════════════

□ 1.1  إنشاء جميع ملفات Migration
       - employees
       - import_batches             (يتضمن حقل import_settings JSON)
       - attendance_records         (بدون deleted_at)
       - payroll_reports
       - settings                   (يتضمن overtime_start_time)
       - public_holidays

□ 1.2  إنشاء جميع Models مع العلاقات
       - Employee (hasMany: records, payrolls)
       - AttendanceRecord (belongsTo: employee, importBatch)  # حذف حقيقي - لا Soft Delete
       - ImportBatch (hasMany: records, publicHolidays)
       - PayrollReport (belongsTo: employee)
       - PublicHoliday (belongsTo: importBatch)
       - Setting

□ 1.3  إنشاء Enums
       - ImportStatus
       - DayOfWeek

□ 1.4  إنشاء Seeders
       - DefaultSettingsSeeder
       - AdminUserSeeder

□ 1.6  تشغيل Migrations و Seeders

□ 1.7  إنشاء Factories للاختبارات
```

### المرحلة 2: نظام المصادقة والصلاحيات (يوم واحد)

```
الخطوات:
═══════════════════════════════════════════

□ 2.1  إعداد Laravel Breeze
       - تعديل صفحة تسجيل الدخول
       - إزالة صفحة التسجيل (لا أحد يسجل نفسه)

□ 2.2  إنشاء CheckRole Middleware

□ 2.3  إعداد Routes مع Middleware groups

□ 2.4  إنشاء Admin User من Seeder
```

### المرحلة 3: إدارة الموظفين (يومين)

```
الخطوات:
═══════════════════════════════════════════

□ 3.1  إنشاء EmployeeService

□ 3.2  إنشاء EmployeeController (CRUD كامل)

□ 3.3  إنشاء Form Requests
       - StoreEmployeeRequest
       - UpdateEmployeeRequest

□ 3.4  إنشاء Views
       - employees/index.blade.php (جدول مع بحث وتقسيم صفحات)
       - employees/create.blade.php (نموذج إضافة)
       - employees/edit.blade.php (نموذج تعديل)
       - employees/show.blade.php (تفاصيل مع سجل الحضور)

□ 3.5  اختبار CRUD كامل
```

### المرحلة 4: استيراد ملف Excel (4 أيام) ⭐ المرحلة الأهم

```
الخطوات:
═══════════════════════════════════════════

□ 4.1  إنشاء DTOs
       - AttendanceRowDTO
       - EmployeeAttendanceDTO

□ 4.2  إنشاء ExcelTimeHelper
       - تحويل أوقات Excel المختلفة
       - التعامل مع التنسيقات المتعددة

□ 4.3  إنشاء ExcelReaderService
       - قراءة الملف باستخدام Maatwebsite

□ 4.4  إنشاء ExcelParserService
       - تحليل الصفوف وتحويلها إلى DTOs
       - تجميع البيانات حسب الموظف
       - التعامل مع الصفوف الفارغة والأخطاء
       - تطبيق القيم الافتراضية للحضور/الانصراف المفقود

□ 4.5  إنشاء ImportService
       - تنسيق عملية الاستيراد الكاملة
       - فحص التكرار ومنطق الاستبدال
       - إنشاء/تحديث الموظفين تلقائياً
       - حفظ سجلات الحضور
       - استخدام Database Transaction

□ 4.6  إنشاء ImportController و Views
       - نموذج رفع الملف
       - نافذة تأكيد الاستبدال عند التكرار

□ 4.7  إنشاء UploadFileRequest
       - التحقق من النوع والحجم

□ 4.8  إنشاء شاشة التأكيد (confirm.blade.php)
       - قسم "الإعدادات المتقدمة" (Dynamic Settings):
         ├── تجلب الإعدادات الحالية مسبقاً كقيم افتراضية
         ├── حقول اختيارية: work_start, work_end, ot_start
         └── حقول اختيارية: late_rate, absent_rate, ot_rate
       - قسم "الإجازات الرسمية في هذا الشهر" (Public Holidays):
         ├── إدخال تاريخ + اسم الإجازة (يمكن تعدد)
         └── حذف إجازة مدخلة (قبل التأكيد)

□ 4.9  إنشاء PublicHolidayService و PublicHolidayController
       - حفظ الإجازات المرتبطة بدفعة الاستيراد
       - استرجاعها في WorkingDaysService لاستثنائها من حساب الغياب

□ 4.10 ربط Dynamic Settings بعملية الحساب
       - حفظ import_settings (JSON) في ImportBatch
       - ImportService يقرأ import_settings أولاً، ثم يرجع للإعدادات العامة إذا كان null

□ 4.11 إضافة Hard Delete لبيانات الشهر
       - DELETE /import/{batch} مع تأكيد منبثق
       - حذف جميع attendance_records المرتبطة بالـ batch
       - حذف public_holidays المرتبطة
       - حذف الملف من Storage
       - حذف ImportBatch نفسه
       - كل الحذف داخل Transaction

□ 4.12 اختبارات شاملة
       - ملف صحيح
       - ملف فارغ
       - ملف بتنسيق خاطئ
       - ملف مكرر
       - ناقص Clock In أو Clock Out
       - رفع مع إجازات رسمية
       - رفع مع override settings
       - حذف بيانات شهر كامل
```

### المرحلة 5: حساب الحضور والتأخير (يومين)

```
الخطوات:
═══════════════════════════════════════════

□ 5.1  إنشاء WorkingDaysService
       - حساب أيام العمل المفترضة في الشهر
       - مراعاة يوم الجمعة ويوم الإجازة الثاني

□ 5.2  إنشاء AttendanceCalculationService
       - حساب التأخير لكل يوم
       - حساب Overtime لكل يوم
       - حساب ساعات العمل الفعلية

□ 5.3  إنشاء AbsenceDetectionService
       - كشف أيام الغياب
       - مقارنة أيام العمل بسجلات الحضور

□ 5.4  دمج الحسابات مع عملية الاستيراد
       - بعد حفظ السجلات، يتم حساب late_minutes و overtime_minutes

□ 5.5  اختبارات Unit Tests
       - كل حالة من Edge Cases
       - أمثلة حسابية متنوعة
```

### المرحلة 6: تقارير الحضور (يومين)

```
الخطوات:
═══════════════════════════════════════════

□ 6.1  إنشاء AttendanceController

□ 6.2  إنشاء Views
       - attendance/report.blade.php
         ├── فلترة بالشهر والسنة
         ├── فلترة بالموظف
         ├── جدول يعرض جميع الموظفين مع:
         │   ├── أيام الحضور
         │   ├── أيام الغياب
         │   ├── إجمالي التأخير
         │   └── إجمالي Overtime
         └── إمكانية الضغط على موظف لعرض التفاصيل

       - تفاصيل حضور موظف (ضمن employees/show)
         ├── جدول يومي لكل أيام الشهر
         ├── تلوين الأيام (حضور/غياب/إجازة/تأخير)
         └── ملخص الشهر

□ 6.3  تصدير التقرير إلى Excel (اختياري)
```

### المرحلة 7: حساب المرتبات (يومين)

```
الخطوات:
═══════════════════════════════════════════

□ 7.1  إنشاء PayrollCalculationService
       - جمع بيانات الحضور الشهرية
       - تطبيق معادلة الراتب
       - حساب فردي وجماعي

□ 7.2  إنشاء PayrollController

□ 7.3  إنشاء Views
       - payroll/calculate.blade.php
         ├── اختيار الشهر والسنة
         ├── إدخال قيم الحساب (أو استخدام الإعدادات الافتراضية)
         ├── زر "حساب المرتبات"
         └── عرض النتيجة قبل الحفظ

       - payroll/index.blade.php
         ├── كشف مرتبات الشهر
         ├── جدول بكل الموظفين مع تفاصيل الراتب
         ├── إجمالي المرتبات
         ├── زر تأمين الكشف
         └── زر تصدير

□ 7.4  إنشاء PayrollExport (تصدير إلى Excel)

□ 7.5  اختبارات Unit Tests
```

### المرحلة 8: لوحة التحكم Dashboard (يوم واحد)

```
الخطوات:
═══════════════════════════════════════════

□ 8.1  إنشاء DashboardStatisticsService
       - إجمالي الموظفين
       - نسبة الحضور
       - إجمالي التأخير
       - إجمالي Overtime
       - آخر عمليات الاستيراد

□ 8.2  إنشاء DashboardController

□ 8.3  إنشاء Views مع مكونات:
       - بطاقات الإحصائيات
       - رسم بياني للحضور (Chart.js)
       - قائمة أكثر الموظفين تأخيراً
       - قائمة أكثر الموظفين Overtime
       - حالة آخر استيراد

□ 8.4  إضافة Caching للإحصائيات
```

### المرحلة 9: الإعدادات (يوم واحد)

```
الخطوات:
═══════════════════════════════════════════

□ 9.1  إنشاء SettingService (مع Cache)

□ 9.2  إنشاء SettingController

□ 9.3  إنشاء settings/index.blade.php
       ├── قسم مواعيد العمل
       │   ├── بداية الدوام
       │   ├── نهاية الدوام
       │   └── بداية حساب Overtime
       │
       ├── قسم الإجازات
       │   └── يوم الإجازة الأسبوعي الثاني
       │
       └── قسم المالية
           ├── قيمة خصم ساعة التأخير
           ├── قيمة ساعة Overtime
           └── قيمة خصم يوم الغياب

□ 9.4  إنشاء UpdateSettingsRequest
```

### المرحلة 10: التحسينات والاختبارات النهائية (يومين)

```
الخطوات:
═══════════════════════════════════════════

□ 10.1  مراجعة شاملة للكود (Code Review)

□ 10.2  إضافة Form Validation Messages بالعربي

□ 10.3  إضافة Flash Messages للعمليات الناجحة والفاشلة

□ 10.4  تحسين واجهة المستخدم
        - Responsive Design
        - Loading States
        - Confirmation Dialogs

□ 10.5  إنشاء Feature Tests شاملة

□ 10.6  اختبار الأداء مع بيانات كبيرة

□ 10.7  إعداد ملف README.md

□ 10.8  إعداد ملف .env.example

□ 10.9  مراجعة الأمان النهائية
```

---

## 📊 ملخص الجدول الزمني

| المرحلة | الوصف | المدة المتوقعة |
|---------|-------|----------------|
| المرحلة 0 | التجهيز والإعداد | يوم واحد |
| المرحلة 1 | قاعدة البيانات والنماذج | يومين |
| المرحلة 2 | المصادقة والصلاحيات | يوم واحد |
| المرحلة 3 | إدارة الموظفين | يومين |
| المرحلة 4 | استيراد ملف Excel + إجازات + Dynamic Settings ⭐ | 4 أيام |
| المرحلة 5 | حساب الحضور والتأخير | يومين |
| المرحلة 6 | تقارير الحضور | يوم واحد |
| المرحلة 7 | حساب المرتبات | يومين |
| المرحلة 8 | لوحة التحكم | يوم واحد |
| المرحلة 9 | الإعدادات | يوم واحد |
| المرحلة 10 | التحسينات والاختبارات | يومين |
| **الإجمالي** | | **15 يوم عمل** |

---

## 🛠️ الحزم والتقنيات المستخدمة

| الحزمة | الإصدار | الاستخدام |
|--------|---------|-----------|
| Laravel | 10.x | إطار العمل الأساسي |
| PHP | 8.1 | لغة البرمجة |
| MySQL | 8.0+ | قاعدة البيانات |
| Laravel Breeze | ^1.0 | نظام المصادقة |
| Maatwebsite/Excel | ^3.1 | قراءة وكتابة Excel |
| Chart.js | 4.x | الرسوم البيانية في Dashboard |
| Tailwind CSS | 3.x | تصميم الواجهة (يأتي مع Breeze) |
| Alpine.js | 3.x | تفاعلية الواجهة (يأتي مع Breeze) |

> **ملاحظة:** جميع الحزم متوافقة مع PHP 8.1 ولا تتطلب إصدار أعلى.

---

## 📝 ملاحظات ختامية

1. **هذه الخطة هي وثيقة حية** — يمكن تعديلها وتحديثها أثناء التنفيذ.
2. **الأولوية للوظائف الأساسية** — رفع الملف، حساب الحضور، حساب الراتب.
3. **الكود نظيف وقابل للتوسع** — استخدام Services يسهل إضافة ميزات جديدة.
4. **الاختبارات ضرورية** — خاصة لخوارزميات الحساب لضمان الدقة.
5. **التعامل مع Edge Cases** — يجب تغطيتها في الاختبارات وليس فقط في الكود.

---

> **تم إعداد هذه الوثيقة بواسطة:** GitHub Copilot - Senior Software Architect  
> **تاريخ:** 2026-03-11
