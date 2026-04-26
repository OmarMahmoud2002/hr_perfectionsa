<div style="margin:0 0 18px;padding:18px 20px;border:1px solid #dbe7f3;border-radius:22px;background:linear-gradient(135deg,#f8fbff 0%,#f3faf8 100%);">
    @if(!empty($eyebrow ?? null))
        <div style="font-size:12px;font-weight:800;color:#31719d;letter-spacing:.08em;">{{ $eyebrow }}</div>
    @endif
    <h2 style="margin:10px 0 8px;font-size:28px;line-height:1.45;color:#0f4c81;">{{ $heading ?? ($title ?? 'إشعار جديد') }}</h2>
    @if(!empty($intro ?? null))
        <p style="margin:0;color:#475569;font-size:15px;line-height:2;">{{ $intro }}</p>
    @endif
</div>

@if(!empty($details ?? []))
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 18px;border-collapse:separate;border-spacing:0;background:#ffffff;border:1px solid #e2ebf5;border-radius:22px;overflow:hidden;">
        <tr>
            <td style="padding:18px 20px 10px;font-size:18px;font-weight:800;color:#0f172a;">تفاصيل الإشعار</td>
        </tr>
        <tr>
            <td style="padding:0 20px 18px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    @foreach($details as $label => $value)
                        <tr>
                            <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:14px;color:#64748b;width:34%;vertical-align:top;">{{ $label }}</td>
                            <td style="padding:12px 0;border-bottom:1px solid #e5edf5;font-size:15px;color:#0f172a;font-weight:700;">
                                <span dir="auto" style="unicode-bidi:isolate;display:inline-block;line-height:1.9;">{{ $value }}</span>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>
@endif

<div style="text-align:center;margin-top:22px;">
    <a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 28px;border-radius:14px;background:linear-gradient(135deg,#0f4c81 0%,#27699d 100%);color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;box-shadow:0 14px 30px rgba(15,76,129,.18);">{{ $actionText }}</a>
</div>
