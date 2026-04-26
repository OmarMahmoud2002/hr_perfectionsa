@php
    $imageSrc = null;

    if (! empty($embeddedImagePath) && isset($message) && is_file($embeddedImagePath)) {
        $imageSrc = $message->embed($embeddedImagePath);
    } elseif (! empty($imageUrl)) {
        $imageSrc = $imageUrl;
    }

    $slot = '
        <div style="font-size:15px;color:#1f2937;line-height:2;">
            <div style="margin:0 0 18px;padding:18px 20px;border:1px solid #dbe7f3;border-radius:22px;background:linear-gradient(135deg,#f8fbff 0%,#f3faf8 100%);">
                <div style="font-size:12px;font-weight:800;color:#31719d;letter-spacing:.08em;">إشعار داخلي</div>
                <div style="margin-top:8px;font-size:15px;color:#334155;">
                    وصلك إشعار جديد من
                    <strong style="color:#0f4c81;" dir="auto">'.e($senderName ?? 'الإدارة').'</strong>
                </div>
            </div>

            <div style="margin:0 0 18px;padding:20px;border:1px solid #e2ebf5;border-radius:22px;background:#ffffff;box-shadow:0 12px 28px rgba(15,76,129,.06);">
                <div style="font-size:22px;font-weight:800;color:#0f172a;line-height:1.5;margin-bottom:12px;" dir="auto">'.e($title ?? 'إشعار جديد').'</div>
                <div style="white-space:pre-line;color:#334155;font-size:15px;line-height:2;" dir="auto">'.nl2br(e($messageBody ?? '')).'</div>
            </div>

            '.(! empty($imageSrc)
                ? '<div style="margin:0 0 18px;padding:10px;border:1px solid #dbe7f3;border-radius:22px;background:#ffffff;text-align:center;box-shadow:0 12px 28px rgba(15,76,129,.05);"><img src="'.e($imageSrc).'" alt="'.e($title ?? 'إشعار').'" style="border-radius:16px;display:block;margin:0 auto;max-width:100%;height:auto;"></div>'
                : '').'

            '.(! empty($linkUrl)
                ? '<div style="margin:0 0 18px;padding:16px 18px;border:1px solid #dbe7f3;border-radius:18px;background:#f8fbff;"><div style="font-size:13px;font-weight:700;color:#31719d;margin-bottom:8px;">رابط مرفق</div><a href="'.e($linkUrl).'" dir="ltr" style="color:#0f4c81;font-weight:700;text-decoration:none;word-break:break-all;unicode-bidi:isolate;display:inline-block;">'.e($linkUrl).'</a></div>'
                : '').'

            <div style="margin-top:20px;text-align:center;">
                <a href="'.e($actionUrl ?? url('/')).'" style="display:inline-block;padding:14px 24px;border-radius:999px;background:linear-gradient(135deg,#31719d 0%,#317c77 100%);color:#ffffff;text-decoration:none;font-weight:800;box-shadow:0 14px 30px rgba(49,113,157,.24);">
                    '.e($actionText ?? 'فتح الإشعار').'
                </a>
            </div>

            <p style="margin:18px 0 0;font-size:12px;color:#64748b;text-align:center;">
                ستجد نفس الرسالة أيضًا داخل مركز الإشعارات بالنظام.
            </p>
        </div>
    ';
@endphp

@include('emails._layout', ['title' => $title ?? 'إشعار جديد', 'slot' => $slot])
