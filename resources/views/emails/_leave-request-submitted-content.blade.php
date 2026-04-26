<div style="margin:0 0 18px;padding:18px 20px;border:1px solid #dbe7f3;border-radius:22px;background:linear-gradient(135deg,#f8fbff 0%,#f3faf8 100%);">
    <div style="font-size:12px;font-weight:800;color:#31719d;letter-spacing:.08em;">طلب إجازة جديد</div>
    <h2 style="margin:10px 0 8px;font-size:28px;line-height:1.45;color:#0f4c81;">طلب جديد يحتاج المراجعة</h2>
    <p style="margin:0;color:#475569;font-size:15px;line-height:2;">
        تم تقديم طلب إجازة جديد بواسطة
        <span dir="auto" style="unicode-bidi:isolate;display:inline-block;font-weight:700;color:#0f172a;">{{ $employeeName }}</span>
        ويحتاج إلى مراجعتك واعتماد القرار المناسب.
    </p>
</div>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 18px;border-collapse:separate;border-spacing:0;background:#ffffff;border:1px solid #e2ebf5;border-radius:22px;overflow:hidden;">
    <tr>
        <td style="padding:18px 20px 10px;font-size:18px;font-weight:800;color:#0f172a;">تفاصيل الطلب</td>
    </tr>
    <tr>
        <td style="padding:0 20px 18px;">
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:14px;color:#64748b;width:34%;">اسم الموظف</td>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:15px;color:#0f172a;font-weight:700;">
                        <span dir="auto" style="unicode-bidi:isolate;display:inline-block;">{{ $employeeName }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:14px;color:#64748b;">من</td>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:15px;color:#0f172a;font-weight:700;">
                        <span dir="ltr" style="unicode-bidi:isolate;display:inline-block;">{{ $startDate }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:14px;color:#64748b;">إلى</td>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:15px;color:#0f172a;font-weight:700;">
                        <span dir="ltr" style="unicode-bidi:isolate;display:inline-block;">{{ $endDate }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:14px;color:#64748b;">عدد الأيام</td>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:15px;color:#0f172a;font-weight:700;">
                        <span dir="ltr" style="unicode-bidi:isolate;display:inline-block;">{{ $requestedDays }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 0 0;font-size:14px;color:#64748b;vertical-align:top;">السبب</td>
                    <td style="padding:12px 0 0;font-size:15px;color:#0f172a;font-weight:700;">
                        <span dir="auto" style="unicode-bidi:isolate;display:inline-block;line-height:1.9;">{{ $reason }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div style="text-align:center;margin-top:22px;">
    <a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 28px;border-radius:14px;background:linear-gradient(135deg,#0f4c81 0%,#27699d 100%);color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;box-shadow:0 14px 30px rgba(15,76,129,.18);">{{ $actionText }}</a>
</div>
