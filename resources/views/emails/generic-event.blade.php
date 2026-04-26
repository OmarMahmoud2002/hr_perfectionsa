@php
    $slot = view('emails._generic-event-content', get_defined_vars())->render();
@endphp
@include('emails._layout', ['title' => $title ?? 'إشعار جديد', 'slot' => $slot])
