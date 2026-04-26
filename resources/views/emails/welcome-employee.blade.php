@php
    $slot = view('emails._welcome-employee-content', get_defined_vars())->render();
@endphp
@include('emails._layout', ['title' => $title ?? 'مرحبًا بك', 'slot' => $slot])
