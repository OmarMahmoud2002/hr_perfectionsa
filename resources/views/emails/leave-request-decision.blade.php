@php
    $slot = view('emails._leave-request-decision-content', get_defined_vars())->render();
@endphp
@include('emails._layout', ['title' => $title ?? 'تحديث طلب الإجازة', 'slot' => $slot])
