<div style="margin:0 0 18px;padding:20px 22px;border:1px solid #dbe7f3;border-radius:22px;background:linear-gradient(135deg,#f8fbff 0%,#eff8f4 100%);">
    <div style="font-size:12px;font-weight:800;color:#31719d;letter-spacing:.08em;">رسالة ترحيب</div>
    <h2 style="margin:10px 0 10px;font-size:28px;line-height:1.45;color:#0f4c81;">أهلًا بك في نظام الشركة</h2>
    <p style="margin:0;color:#475569;font-size:15px;line-height:2;">
        يسعدنا انضمامك معنا يا
        <span dir="auto" style="unicode-bidi:isolate;display:inline-block;font-weight:800;color:#0f172a;">{{ $employeeName ?? 'زميلنا العزيز' }}</span>،
        ونتمنى لك بداية موفقة وتجربة عمل مريحة ومنظمة داخل النظام.
    </p>
</div>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 18px;border-collapse:separate;border-spacing:0;background:#ffffff;border:1px solid #e2ebf5;border-radius:22px;overflow:hidden;">
    <tr>
        <td style="padding:18px 20px 10px;font-size:18px;font-weight:800;color:#0f172a;">يمكنك الآن استخدام النظام في</td>
    </tr>
    <tr>
        <td style="padding:0 20px 20px;">
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:15px;color:#334155;">تقديم ومتابعة طلبات الإجازة بسهولة.</td>
                </tr>
                <tr>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:15px;color:#334155;">متابعة المهام المسندة إليك وتحديث حالتها أولًا بأول.</td>
                </tr>
                <tr>
                    <td style="padding:12px 0 0;font-size:15px;color:#334155;">تسجيل ومراجعة الأداء اليومي من مكان واحد.</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div style="margin:0 0 18px;padding:16px 18px;border:1px dashed #c8d8ea;border-radius:18px;background:#f8fbff;color:#475569;font-size:14px;line-height:2;">
    ننصحك بعد أول تسجيل دخول أن تقوم بتحديث كلمة المرور ومراجعة بيانات حسابك لضمان أفضل تجربة استخدام.
</div>

<div style="text-align:center;margin-top:22px;">
    <a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 28px;border-radius:14px;background:linear-gradient(135deg,#0f4c81 0%,#27699d 100%);color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;box-shadow:0 14px 30px rgba(15,76,129,.18);">{{ $actionText }}</a>
</div>
