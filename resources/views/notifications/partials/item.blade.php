@php
    $compact = (bool) ($compact ?? false);
    $data = $notification->data ?? [];
    $title = (string) ($data['title'] ?? 'إشعار جديد');
    $message = (string) ($data['message'] ?? '');
    $url = (string) ($data['url'] ?? '#');
    $openUrl = $url !== '#' ? route('notifications.open', ['notificationId' => $notification->id]) : '#';
    $type = (string) ($data['type'] ?? 'general');
    $isUnread = $notification->read_at === null;

    $meta = match ($type) {
        'leave_request_submitted' => [
            'label' => 'طلبات الإجازات',
            'accent' => 'from-sky-500 to-cyan-500',
            'badge' => 'bg-sky-100 text-sky-700',
            'dot' => 'bg-sky-500',
            'action' => 'مراجعة الطلب',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
        ],
        'leave_request_decision' => [
            'label' => 'قرارات الإجازات',
            'accent' => 'from-emerald-500 to-teal-500',
            'badge' => 'bg-emerald-100 text-emerald-700',
            'dot' => 'bg-emerald-500',
            'action' => 'عرض القرار',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
        ],
        'task_assigned' => [
            'label' => 'المهام',
            'accent' => 'from-amber-500 to-orange-500',
            'badge' => 'bg-amber-100 text-amber-700',
            'dot' => 'bg-amber-500',
            'action' => 'فتح المهمة',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V9m-5-4h5m0 0v5m0-5L10 14"/></svg>',
        ],
        'task_completed' => [
            'label' => 'إنجاز المهام',
            'accent' => 'from-emerald-500 to-teal-500',
            'badge' => 'bg-emerald-100 text-emerald-700',
            'dot' => 'bg-emerald-500',
            'action' => 'مراجعة المهمة',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
        ],
        'task_evaluated' => [
            'label' => 'تقييم المهام',
            'accent' => 'from-cyan-500 to-sky-600',
            'badge' => 'bg-cyan-100 text-cyan-700',
            'dot' => 'bg-cyan-500',
            'action' => 'عرض التقييم',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.037 3.193a1 1 0 00.95.69h3.357c.969 0 1.371 1.24.588 1.81l-2.716 1.973a1 1 0 00-.364 1.118l1.037 3.193c.3.921-.755 1.688-1.538 1.118l-2.716-1.973a1 1 0 00-1.176 0l-2.716 1.973c-.783.57-1.838-.197-1.538-1.118l1.037-3.193a1 1 0 00-.364-1.118L5.117 8.62c-.783-.57-.38-1.81.588-1.81h3.357a1 1 0 00.95-.69l1.037-3.193z"/></svg>',
        ],
        'daily_performance_reviewed' => [
            'label' => 'الأداء اليومي',
            'accent' => 'from-violet-500 to-indigo-600',
            'badge' => 'bg-violet-100 text-violet-700',
            'dot' => 'bg-violet-500',
            'action' => 'عرض الأداء',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m3 6V7m3 10v-3m3 7H6a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2v14a2 2 0 01-2 2z"/></svg>',
        ],
        'welcome_employee' => [
            'label' => 'الحسابات',
            'accent' => 'from-violet-500 to-fuchsia-500',
            'badge' => 'bg-violet-100 text-violet-700',
            'dot' => 'bg-violet-500',
            'action' => 'بدء الاستخدام',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M7 16.938A9 9 0 1117 16.938"/></svg>',
        ],
        'employee_of_month_published' => [
            'label' => 'أفضل موظف',
            'accent' => 'from-indigo-500 to-blue-600',
            'badge' => 'bg-indigo-100 text-indigo-700',
            'dot' => 'bg-indigo-500',
            'action' => 'عرض النتائج',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.037 3.193a1 1 0 00.95.69h3.357c.969 0 1.371 1.24.588 1.81l-2.716 1.973a1 1 0 00-.364 1.118l1.037 3.193c.3.921-.755 1.688-1.538 1.118l-2.716-1.973a1 1 0 00-1.176 0l-2.716 1.973c-.783.57-1.838-.197-1.538-1.118l1.037-3.193a1 1 0 00-.364-1.118L5.117 8.62c-.783-.57-.38-1.81.588-1.81h3.357a1 1 0 00.95-.69l1.037-3.193z"/></svg>',
        ],
        'announcement_broadcast' => [
            'label' => 'إشعار إداري',
            'accent' => 'from-sky-600 to-teal-500',
            'badge' => 'bg-sky-100 text-sky-700',
            'dot' => 'bg-sky-500',
            'action' => 'فتح الإشعار',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m8-8L3 11l7 2 2 7L21 4z"/></svg>',
        ],
        'attendance_month_imported' => [
            'label' => 'الحضور الشهري',
            'accent' => 'from-blue-500 to-cyan-600',
            'badge' => 'bg-blue-100 text-blue-700',
            'dot' => 'bg-blue-500',
            'action' => 'فتح حسابي',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
        ],
        default => [
            'label' => 'عام',
            'accent' => 'from-slate-500 to-slate-700',
            'badge' => 'bg-slate-100 text-slate-700',
            'dot' => 'bg-slate-500',
            'action' => 'فتح',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        ],
    };

    $containerClasses = $compact ? 'px-3 py-3' : 'p-4 sm:p-5';
@endphp

<div class="{{ $containerClasses }}">
    <div class="rounded-3xl border {{ $isUnread ? 'border-slate-200 bg-white shadow-md shadow-slate-200/70' : 'border-slate-100 bg-slate-50/85' }} p-4 sm:p-5 transition-all">
        <div class="flex items-start gap-3.5">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $meta['accent'] }} text-white shadow-sm ring-1 ring-white/60">
                {!! $meta['icon'] !!}
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full {{ $isUnread ? $meta['dot'] : 'bg-slate-300' }}"></span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold {{ $meta['badge'] }}">
                        {{ $meta['label'] }}
                    </span>
                    @if($isUnread)
                        <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-black text-rose-700">
                            جديد
                        </span>
                    @endif
                    <span class="mr-auto text-[11px] font-semibold text-slate-400 whitespace-nowrap">{{ $notification->created_at?->diffForHumans() }}</span>
                </div>

                <h4 class="mt-2 text-[15px] font-black leading-6 text-slate-800">{{ $title }}</h4>

                @if($message !== '')
                    <p class="mt-1 text-sm leading-6 text-slate-600 break-words" style="display:-webkit-box;line-clamp:3;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">{{ $message }}</p>
                @endif

                @if($type === 'announcement_broadcast' && filled($data['sender_name'] ?? null))
                    <p class="mt-2 text-xs font-semibold text-slate-500">من: {{ $data['sender_name'] }}</p>
                @endif

                <div class="mt-3 flex flex-wrap items-center gap-2.5">
                    @if($openUrl !== '#')
                        <a href="{{ $openUrl }}" class="inline-flex items-center rounded-xl bg-sky-100 px-3 py-2 text-xs font-black text-slate-900 transition hover:bg-sky-200">
                            {{ $meta['action'] }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
