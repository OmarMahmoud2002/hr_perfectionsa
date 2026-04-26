<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Perfection System' }}</title>
</head>
@php
    $brandName = 'Perfection System';
    $brandLogoPath = public_path('logo3.png');

    if (! is_file($brandLogoPath)) {
        $fallbackLogo = public_path('logo.png');
        $brandLogoPath = is_file($fallbackLogo) ? $fallbackLogo : null;
    }

    $brandLogoSrc = null;
    if ($brandLogoPath && isset($message)) {
        $brandLogoSrc = $message->embed($brandLogoPath);
    } elseif ($brandLogoPath) {
        $brandLogoSrc = asset(basename($brandLogoPath));
    }
@endphp
<body style="margin:0;padding:0;background:#edf4f7;font-family:Tahoma,Arial,sans-serif;color:#1f2937;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:radial-gradient(circle at top right, rgba(49,113,157,.08), transparent 28%), linear-gradient(180deg,#edf4f7 0%,#f8fafc 100%);padding:30px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="680" style="max-width:680px;background:#ffffff;border-radius:28px;overflow:hidden;border:1px solid #dbe5f1;box-shadow:0 18px 40px rgba(15,76,129,.12);">
                    <tr>
                        <td style="padding:26px 26px 22px;background:radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 30%), linear-gradient(135deg,#2f6f99 0%,#2e6d98 42%,#317c77 100%);text-align:center;">
                            @if($brandLogoSrc)
                                <div style="margin:0 auto 16px;width:140px;max-width:100%;display:flex;align-items:center;justify-content:center;">
                                    <img src="{{ $brandLogoSrc }}" alt="{{ $brandName }}" style="display:block;max-width:120px;height:auto;position:relative;right:55px;">
                                </div>
                            @endif
                            <div style="display:inline-block;background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.22);padding:8px 16px;border-radius:999px;color:#e8f4ff;font-size:11px;font-weight:800;letter-spacing:0.08em;">
                                PERFECTION SYSTEM
                            </div>
                            <div style="margin-top:14px;font-size:28px;line-height:1.2;font-weight:800;color:#ffffff;">
                                {{ $brandName }}
                            </div>
                            <div style="margin-top:8px;color:#d7ecff;font-size:13px;line-height:1.9;">
                                رسائل وإشعارات العمل في تجربة أوضح وأكثر أناقة
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 24px 14px;line-height:1.9;font-size:15px;color:#1f2937;">
                            {!! $slot !!}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;border-spacing:0;background:#f8fbff;border:1px solid #e2ebf5;border-radius:18px;">
                                <tr>
                                    <td style="padding:16px 18px;color:#4b5563;font-size:12px;line-height:2;">
                                        <strong style="color:#0f4c81;">ملاحظة:</strong>
                                        هذه رسالة تلقائية من
                                        <span dir="ltr" style="unicode-bidi:isolate;display:inline-block;">{{ $brandName }}</span>.
                                        في حال وجود أي استفسار يمكنك التواصل مع فريق الموارد البشرية أو إدارة النظام.
                                        <br>
                                        رابط النظام:
                                        <a href="{{ url('/') }}" dir="ltr" style="color:#0f4c81;font-weight:800;text-decoration:none;unicode-bidi:isolate;display:inline-block;">{{ url('/') }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <table role="presentation" cellpadding="0" cellspacing="0" width="680" style="max-width:680px;margin-top:10px;">
                    <tr>
                        <td align="center" style="color:#8a97a8;font-size:11px;line-height:1.8;padding:4px 0;">
                            © {{ date('Y') }}
                            <span dir="ltr" style="unicode-bidi:isolate;display:inline-block;">{{ $brandName }}</span>
                            - جميع الحقوق محفوظة
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
