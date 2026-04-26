@php
    $slot = view('emails._task-assigned-content', get_defined_vars())->render();
@endphp
@include('emails._layout', ['title' => $title ?? 'مهمة جديدة', 'slot' => $slot])
