@php
    $decisionIsApproved = ($decisionKey ?? '') === 'approved';
    $statusBadgeBackground = $decisionIsApproved ? '#e7f8ef' : '#fff1f2';
    $statusBadgeColor = $decisionIsApproved ? '#117a46' : '#c2410c';
@endphp

<div style="margin:0 0 18px;padding:18px 20px;border:1px solid #dbe7f3;border-radius:22px;background:linear-gradient(135deg,#f8fbff 0%,#f6fbff 60%,#f4faf6 100%);">
    <div style="font-size:12px;font-weight:800;color:#31719d;letter-spacing:.08em;">تحديث طلب الإجازة</div>
    <h2 style="margin:10px 0 12px;font-size:28px;line-height:1.45;color:#0f4c81;">تم تحديث حالة طلب الإجازة</h2>
    <div style="margin-bottom:12px;">
        <span style="display:inline-block;padding:8px 14px;border-radius:999px;background:{{ $statusBadgeBackground }};color:{{ $statusBadgeColor }};font-size:13px;font-weight:800;">{{ $decisionLabel }}</span>
    </div>
    <p style="margin:0;color:#475569;font-size:15px;line-height:2;">
        تم اتخاذ القرار النهائي بواسطة
        <span dir="auto" style="unicode-bidi:isolate;display:inline-block;font-weight:700;color:#0f172a;">{{ $actorName }}</span>.
    </p>
</div>

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 18px;border-collapse:separate;border-spacing:0;background:#ffffff;border:1px solid #e2ebf5;border-radius:22px;overflow:hidden;">
    <tr>
        <td style="padding:18px 20px 10px;font-size:18px;font-weight:800;color:#0f172a;">ملخص القرار</td>
    </tr>
    <tr>
        <td style="padding:0 20px 18px;">
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                <tr>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:14px;color:#64748b;width:38%;">الحالة الحالية</td>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:15px;color:#0f172a;font-weight:700;">
                        <span dir="auto" style="unicode-bidi:isolate;display:inline-block;">{{ $statusLabel }}</span>
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
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:14px;color:#64748b;">الأيام المطلوبة</td>
                    <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:15px;color:#0f172a;font-weight:700;">
                        <span dir="ltr" style="unicode-bidi:isolate;display:inline-block;">{{ $requestedDays }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 0 0;font-size:14px;color:#64748b;">الأيام المعتمدة نهائيًا</td>
                    <td style="padding:12px 0 0;font-size:15px;color:#0f172a;font-weight:700;">
                        <span dir="ltr" style="unicode-bidi:isolate;display:inline-block;">{{ $finalApprovedDays }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div style="text-align:center;margin-top:22px;">
    <a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 28px;border-radius:14px;background:linear-gradient(135deg,#0f4c81 0%,#27699d 100%);color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;box-shadow:0 14px 30px rgba(15,76,129,.18);">{{ $actionText }}</a>
</div>
