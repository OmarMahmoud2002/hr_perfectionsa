@php
    $slot = view('emails._leave-request-submitted-content', get_defined_vars())->render();
@endphp
@include('emails._layout', ['title' => $title ?? 'طلب إجازة جديد', 'slot' => $slot])
