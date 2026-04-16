@extends('layouts.app')

@section('title', 'تسجيل الحضور والانصراف online')
@section('page-title', 'تسجيل الحضور والانصراف online')
@section('page-subtitle', 'تسجيل الحضور والانصراف online')

@section('content')
@php
    $clockIn = $todayRecord?->clock_in ? substr($todayRecord->clock_in, 0, 5) : null;
    $clockOut = $todayRecord?->clock_out ? substr($todayRecord->clock_out, 0, 5) : null;
    $allowedLocations = collect($allowedLocations ?? []);
    $allowRemoteWithoutLocation = (bool) ($allowRemoteWithoutLocation ?? false);

    $todayDate = now()->toDateString();
    $scheduledRemoteDaysThisMonth = collect($scheduledRemoteDaysThisMonth ?? []);
    $isTodayScheduledRemote = $scheduledRemoteDaysThisMonth->contains($todayDate);

    $canUseRemoteAttendance = $employee
        && $employee->is_remote_worker
        && ($allowRemoteWithoutLocation || $allowedLocations->isNotEmpty())
        && $isTodayScheduledRemote;

    $action = null;
    $buttonText = null;

    if ($canUseRemoteAttendance && !$clockIn) {
        $action = 'check-in';
        $buttonText = 'تسجيل الحضور';
    } elseif ($canUseRemoteAttendance && $clockIn && !$clockOut) {
        $action = 'check-out';
        $buttonText = 'تسجيل الانصراف';
    }
@endphp

<div class="max-w-3xl mx-auto space-y-5">
    <div class="card card-interactive p-5">
        <h3 class="text-sm font-bold text-slate-700 mb-4">حالة اليوم</h3>

        @if(!$employee)
            <div class="alert-warning">
                <p>حسابك غير مرتبط بملف موظف. برجاء التواصل مع الإدارة.</p>
            </div>
        @elseif(!$employee->is_remote_worker)
            <div class="alert-warning">
                <p>غير مسموح لك بالحضور الأونلاين. برجاء الرجوع للإدارة.</p>
            </div>
        @elseif(!$allowRemoteWithoutLocation && $allowedLocations->isEmpty())
            <div class="alert-warning">
                <p>لم يتم تحديد أي موقع اونلاين لك حتى الآن. برجاء التواصل مع الإدارة.</p>
            </div>
        @elseif(!$isTodayScheduledRemote)
            <div class="alert-warning">
                <p>اليوم غير مجدول لك كدوام اونلاين، لذلك لا يمكن تسجيل الحضور الأونلاين اليوم.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
                    <p class="text-xs text-emerald-700">الحضور</p>
                    <p class="text-2xl font-black text-emerald-700 mt-1">{{ $clockIn ?? '—' }}</p>
                </div>
                <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-center">
                    <p class="text-xs text-sky-700">الانصراف</p>
                    <p class="text-2xl font-black text-sky-700 mt-1">{{ $clockOut ?? '—' }}</p>
                </div>
            </div>

            @if($action)
                <div class="mt-5">
                    <button id="remote-attendance-btn"
                            type="button"
                            data-action="{{ $action }}"
                            data-check-in-url="{{ route('attendance.check-in') }}"
                            data-check-out-url="{{ route('attendance.check-out') }}"
                            class="btn-primary btn-lg w-full justify-center">
                        {{ $buttonText }}
                    </button>
                    <p id="remote-attendance-message" class="text-xs text-slate-500 mt-2 text-center">سيتم التحقق من موقعك الحالي قبل الحفظ.</p>
                </div>
            @else
                <div class="alert-success mt-5">
                    <p>تم تسجيل الحضور والانصراف لليوم بنجاح.</p>
                </div>
            @endif
        @endif
    </div>

    @if($employee && $allowRemoteWithoutLocation)
        <div class="card card-interactive p-5">
            <h3 class="text-sm font-bold text-slate-700 mb-3">إعداد الموقع</h3>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                <p class="font-semibold text-emerald-700">مفعل: بدون عنوان</p>
                <p class="text-xs text-emerald-700 mt-1">يمكنك التسجيل من أي مكان دون التقيد بالمواقع المعتمدة.</p>
            </div>
        </div>
    @elseif($employee && $allowedLocations->isNotEmpty())
        <div class="card card-interactive p-5">
            <h3 class="text-sm font-bold text-slate-700 mb-3">المواقع المسموح بها لك</h3>
            <div class="space-y-2">
                @foreach($allowedLocations as $location)
                    <div class="rounded-xl border border-slate-200 p-3">
                        <p class="font-semibold text-slate-700">{{ $location->name }}</p>
                        <p class="text-xs text-slate-500 mt-1">نطاق السماح: {{ (int) $location->radius }} متر</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(($scheduledRemoteDaysThisMonth ?? collect())->filter()->isNotEmpty())
        <div class="card card-interactive p-5">
            <h3 class="text-sm font-bold text-slate-700 mb-3">أيام الاونلاين المجدولة لهذا الشهر</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($scheduledRemoteDaysThisMonth->filter() as $day)
                    <span class="px-2.5 py-1 rounded-full border text-xs {{ $day === $todayDate ? 'border-sky-300 bg-sky-100 text-slate-900' : 'border-slate-200 text-slate-700 bg-slate-50' }}">
                        {{ $day }}
                    </span>
                @endforeach
            </div>
        </div>
    @elseif($employee)
        <div class="empty-state border border-amber-200 bg-amber-50 animate-fade-in">
            <div class="empty-state-icon !mb-3" style="background: linear-gradient(135deg, rgba(245,158,11,.2), rgba(217,119,6,.15));">
                <svg class="w-9 h-9 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 8v4m0 4h.01M3.33 18h17.34c1.54 0 2.5-1.67 1.73-3L13.73 3c-.77-1.33-2.69-1.33-3.46 0L1.6 15c-.77 1.33.19 3 1.73 3z"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold text-amber-800 mb-2">لا توجد أيام اونلاين مجدولة هذا الشهر</h3>
            <p class="text-xs text-amber-700">لن يظهر زر التسجيل إلا في الأيام المجدولة لك كدوام اونلاين.</p>
        </div>
    @endif

    @if(($remoteRecordsThisMonth ?? collect())->isNotEmpty())
        <div class="card card-interactive p-5">
            <h3 class="text-sm font-bold text-slate-700 mb-3">الأيام المسجلة اونلاين خلال هذا الشهر</h3>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th class="text-center">الحضور</th>
                            <th class="text-center">الانصراف</th>
                            <th>مكان التسجيل</th>
                            <th>مكان الانصراف</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($remoteRecordsThisMonth as $record)
                            <tr>
                                <td class="font-mono text-xs text-slate-700">{{ data_get($record, 'date') }}</td>
                                <td class="text-center">{{ data_get($record, 'clock_in') ? substr((string) data_get($record, 'clock_in'), 0, 5) : '—' }}</td>
                                <td class="text-center">{{ data_get($record, 'clock_out') ? substr((string) data_get($record, 'clock_out'), 0, 5) : '—' }}</td>
                                <td class="text-slate-700">{{ data_get($record, 'check_in_location_name', data_get($record, 'location_name', 'غير محدد')) }}</td>
                                <td class="text-slate-700">{{ data_get($record, 'check_out_location_name', '—') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    var btn = document.getElementById('remote-attendance-btn');
    var messageEl = document.getElementById('remote-attendance-message');

    if (!btn || !messageEl) {
        return;
    }

    function setMessage(text, isError) {
        messageEl.textContent = text;
        messageEl.classList.toggle('text-red-600', !!isError);
        messageEl.classList.toggle('text-slate-500', !isError);
    }

    function getActionUrl() {
        var action = btn.dataset.action;
        if (action === 'check-in') {
            return btn.dataset.checkInUrl;
        }

        return btn.dataset.checkOutUrl;
    }

    function pad2(value) {
        return String(value).padStart(2, '0');
    }

    function buildClientLocalPayload() {
        var now = new Date();
        var date = now.getFullYear() + '-' + pad2(now.getMonth() + 1) + '-' + pad2(now.getDate());
        var time = pad2(now.getHours()) + ':' + pad2(now.getMinutes()) + ':' + pad2(now.getSeconds());
        var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'unknown';

        return {
            client_local_date: date,
            client_local_time: time,
            client_timezone: tz,
            client_timezone_offset_minutes: -now.getTimezoneOffset(),
        };
    }

    btn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            setMessage('المتصفح لا يدعم تحديد الموقع الجغرافي.', true);
            return;
        }

        btn.disabled = true;
        setMessage('جاري تحديد موقعك الحالي...', false);

        navigator.geolocation.getCurrentPosition(function (position) {
            var token = document.querySelector('meta[name="csrf-token"]');
            var url = getActionUrl();
            var clientLocal = buildClientLocalPayload();

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    client_local_date: clientLocal.client_local_date,
                    client_local_time: clientLocal.client_local_time,
                    client_timezone: clientLocal.client_timezone,
                    client_timezone_offset_minutes: clientLocal.client_timezone_offset_minutes,
                }),
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok) {
                            throw new Error(data.message || 'تعذر إتمام العملية.');
                        }

                        return data;
                    });
                })
                .then(function (data) {
                    setMessage(data.message || 'تم الحفظ بنجاح.', false);
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 700);
                })
                .catch(function (error) {
                    setMessage(error.message || 'حدث خطأ أثناء حفظ الحضور.', true);
                    btn.disabled = false;
                });
        }, function (error) {
            btn.disabled = false;

            if (error && error.code === 1) {
                setMessage('تم رفض إذن الموقع. برجاء السماح بالوصول للموقع ثم المحاولة مرة أخرى.', true);
                return;
            }

            setMessage('تعذر قراءة الموقع الحالي. تأكد من تشغيل GPS ثم أعد المحاولة.', true);
        }, {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0,
        });
    });
})();
</script>
@endpush
