# قواعد صلاحيات الوصول (Access Control Rules)

تاريخ التحديث: 2026-04-09

هذه الوثيقة مرجع رسمي لصلاحيات الوصول الحساسة في النظام. المصدر النهائي للمنع/السماح هو السيرفر (Route Middleware + Policy + Service Scope)، وليس الواجهة.

## مبادئ أساسية

1. أي Query Parameter من العميل غير موثوق به أمنيًا.
2. نمط العرض (مثل cards=1) لا يغير نطاق البيانات أبدًا.
3. توسيع نطاق البيانات الحساسة لا يتم عبر الرابط.
4. إخفاء عنصر في الواجهة لا يعتبر حماية.

## مسارات حساسة

1. /employees
2. /employees/{employee}
3. /leave/approvals
4. /leave/approvals/employee-settings
5. /leave/approvals/{leaveRequest}/decide

## مصفوفة الصلاحيات المعتمدة

| الدور | /employees | /employees/{employee} | /leave/approvals | /leave/approvals/employee-settings |
|---|---:|---:|---:|---:|
| admin | 200 | 200 | 200 | 200 |
| manager | 200 | 200 | 200 | 200 |
| hr | 200 | 200 | 200 | 200 |
| department_manager | 200 (داخل النطاق) | 200 داخل القسم فقط | 200 | 403 |
| employee | 200 (نفسه فقط فعليًا) | 403 | 403 | 403 |
| office_girl | 200 (نفسه فقط فعليًا) | 403 | 403 | 403 |
| user (evaluator) | 403 | 403 | 403 | 403 |

## ضوابط التنفيذ في الكود

1. نطاق موظفي /employees يطبق عبر DepartmentScopeService داخل الخدمة.
2. عرض تفاصيل /employees/{employee} محكوم بـ can:view,employee.
3. /leave/approvals محكوم بـ role + can:viewAny, LeaveRequest.
4. /leave/approvals/{leaveRequest}/decide محكوم بـ can:approve,leaveRequest.
5. /leave/approvals/employee-settings محكوم بـ role:admin,manager,hr فقط.

## اختبارات مرجعية

1. tests/Feature/DepartmentManagerVisibilityTest.php
2. tests/Feature/AccessControlHardeningTest.php
3. tests/Feature/AccessControlAccountMatrixTest.php

## إجراءات عند أي تعديل جديد

1. لا تضف أي صلاحية حساسة تعتمد على query string.
2. حدّث هذه الوثيقة عند تعديل المصفوفة.
3. شغّل اختبارات الصلاحيات قبل الدمج.
