@extends('layouts.app')

@section('title', 'موظف الشهر')
@section('page-title', 'موظف الشهر')
@section('page-subtitle', 'صوّت مرة واحدة خلال دورة 22 إلى 21')

@section('content')
@php
    $statusReasonLabel = [
        'ok' => 'التصويت متاح الآن',
        'already_voted' => 'You already voted',
        'voting_closed' => 'Voting is closed for this month',
        'ineligible_voter' => 'غير مؤهل للتصويت',
    ][$voteStatus['reason']] ?? 'حالة التصويت غير معروفة';
@endphp

<div x-data="employeeMonthVotePage({
        initialStatus: @js($voteStatus),
        candidates: @js($candidates->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'ac_no' => $c->ac_no,
            'position_line' => $c->position_line,
            'avatar' => $c->user?->profile?->avatar_path
                ? route('media.avatar', ['path' => $c->user->profile->avatar_path])
                : null,
        ])->values()),
        statusUrl: '{{ route('employee-of-month.vote.status') }}',
        voteUrl: '{{ route('employee-of-month.vote.store') }}',
        csrfToken: '{{ csrf_token() }}',
    })" x-init="init()" class="space-y-5">

    <div x-show="popup.open"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 px-4"
         @click="closePopup()">
           <div class="w-full max-w-md rounded-2xl p-5 text-white shadow-2xl border border-white/20"
               style="background: radial-gradient(circle at 80% 20%, rgba(231,197,57,.22), transparent 40%), radial-gradient(circle at 10% 90%, rgba(77,155,151,.24), transparent 45%), linear-gradient(135deg, #2f6e98 0%, #2f7a76 100%);"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-180"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.stop>
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                    <svg x-show="popup.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg x-show="popup.type !== 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-lg font-black text-white" x-text="popup.title"></p>
                    <p class="text-sm text-white/95 mt-1" x-text="popup.message"></p>
                </div>
                <button type="button" class="text-white/90 hover:text-white" @click="closePopup()">✕</button>
            </div>
        </div>
    </div>

    <div class="card p-0 overflow-hidden relative">
        <div class="absolute inset-0 opacity-95"
             style="background: radial-gradient(circle at 80% 20%, rgba(231,197,57,.26), transparent 40%), radial-gradient(circle at 10% 90%, rgba(77,155,151,.28), transparent 45%), linear-gradient(135deg, #2f6e98 0%, #2f7a76 100%);"></div>

        <div class="relative p-6 sm:p-7 text-white">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.22em] text-white/70 mb-2">Employee Of The Month</p>
                    <h2 class="text-2xl sm:text-3xl font-black leading-tight">صوتك يفرق في اختيار الموظف المثالي</h2>
                    <p class="text-sm text-white/80 mt-2">تصويت واحد فقط لكل دورة (من يوم 22 إلى يوم 21) - لا يمكن تعديله بعد الحفظ</p>
                </div>
                <div class="rounded-2xl bg-white/12 border border-white/20 p-4 min-w-[220px] backdrop-blur-sm">
                    <p class="text-xs text-white/70 mb-1">الحالة الحالية</p>
                    <p class="text-base font-bold" x-text="statusLabel"></p>
                    <p class="text-xs text-white/75 mt-2" x-show="!hasVoted && secondsRemaining > 0">يغلق التصويت خلال</p>
                    <p class="text-xl font-black mt-1 tracking-wider" x-show="!hasVoted && secondsRemaining > 0" x-text="countdownLabel"></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card overflow-hidden animate-slide-up" style="animation-delay:70ms; animation-fill-mode:both;">
        <div class="card-header flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            <h3>أوائل الشهر الماضي (أفضل 4) - {{ $previousMonthLabel }}</h3>
        </div>
        <div class="p-5 flex flex-col gap-3" dir="rtl">
            @php
                $rankConfig = [
                    0 => [
                        'border'    => 'border-amber-400',
                        'ring'      => 'ring-2 ring-amber-200',
                        'gradient'  => 'from-amber-50 via-yellow-50 to-white',
                        'medal_img' => asset('images/medal-first.png'),
                        'label'     => 'المركز الأول',
                        'text'      => 'text-amber-700',
                    ],
                    1 => [
                        'border'    => 'border-slate-400',
                        'ring'      => 'ring-2 ring-slate-200',
                        'gradient'  => 'from-slate-50 via-gray-50 to-white',
                        'medal_img' => asset('images/medal-second.png'),
                        'label'     => 'المركز الثاني',
                        'text'      => 'text-slate-600',
                    ],
                    2 => [
                        'border'    => 'border-amber-700/50',
                        'ring'      => 'ring-2 ring-amber-100',
                        'gradient'  => 'from-orange-50 via-amber-50 to-white',
                        'medal_img' => asset('images/medal-third.png'),
                        'label'     => 'المركز الثالث',
                        'text'      => 'text-amber-800',
                    ],
                    3 => [
                        'border'    => 'border-cyan-400/70',
                        'ring'      => 'ring-2 ring-cyan-100',
                        'gradient'  => 'from-cyan-50 via-sky-50 to-white',
                        'medal_img' => asset('images/medal-third.png'),
                        'label'     => 'المركز الرابع',
                        'text'      => 'text-cyan-800',
                    ],
                ];
            @endphp
            @forelse($previousMonthTopThree as $idx => $result)
                @php
                    $emp    = $result->employee;
                    $cfg    = $rankConfig[$idx] ?? $rankConfig[2];
                    $avatarUrl = $emp?->user?->profile?->avatar_path
                        ? route('media.avatar', ['path' => $emp->user->profile->avatar_path])
                        : null;
                    $isTitleHolder = (int) $titleHolderEmployeeId === (int) $result->employee_id;
                @endphp

                {{-- Horizontal card: medal | info | avatar --}}
                <div class="rounded-2xl border {{ $cfg['border'] }} {{ $cfg['ring'] }} bg-gradient-to-l {{ $cfg['gradient'] }} px-4 py-3 flex items-center gap-4 shadow-sm"
                     style="animation: slideUp .4s ease {{ $idx * 80 }}ms both;">

                    {{-- Medal on the left, bigger --}}
                    <img src="{{ $cfg['medal_img'] }}" alt="medal"
                         class="w-16 h-16 object-contain drop-shadow-lg flex-shrink-0">

                    {{-- Info block: rank label (black) + name + job --}}
                    <div class="flex-1 min-w-0 text-right">
                        <p class="text-xs font-bold text-slate-900 mb-0.5">{{ $cfg['label'] }}</p>
                        <p class="font-bold text-slate-800 truncate">{{ $emp?->name ?? '—' }}</p>
                        <p class="text-xs {{ $cfg['text'] }} font-semibold">
                            {{ $emp?->position_line ?? 'موظف' }}
                        </p>
                        @if($isTitleHolder)
                            <!-- <div class="flex items-center justify-end gap-1 text-amber-600 mt-1"> -->
                                <!-- <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg> -->
                                <!-- <span class="text-xs font-bold">حامل اللقب الحالي</span> -->
                            <!-- </div> -->
                        @endif
                    </div>

                    {{-- Avatar on the far right --}}
                    <div class="w-12 h-12 rounded-xl overflow-hidden flex items-center justify-center text-white text-base font-black flex-shrink-0 shadow-md"
                         style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $emp?->name }}" class="w-full h-full object-cover">
                        @else
                            {{ mb_substr((string) ($emp?->name ?? '—'), 0, 1) }}
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-slate-500 py-8">
                    {{ $previousMonthPublished ? 'لا يوجد فائزون مستوفون لحد 90 نقطة للشهر الماضي.' : 'نتائج الشهر الماضي لم تُنشر بعد من الإدارة.' }}
                </div>
            @endforelse
        </div>
    </div>

    <div class="card overflow-hidden animate-slide-up" style="animation-delay:95ms; animation-fill-mode:both;">
        <div class="card-header flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-3.314 0-6 2.239-6 5s2.686 5 6 5 6-2.239 6-5-2.686-5-6-5zm0-5l2 3h-4l2-3z"/>
            </svg>
            <h3>مدير الشهر الماضي - {{ $previousMonthLabel }}</h3>
        </div>
        <div class="p-5">
            @if($previousMonthBestManager)
                @php
                    $manager = $previousMonthBestManager['manager'];
                    $managerAvatar = $manager?->user?->profile?->avatar_path
                        ? route('media.avatar', ['path' => $manager->user->profile->avatar_path])
                        : null;
                @endphp
                <div class="rounded-2xl border border-cyan-200 bg-gradient-to-l from-cyan-50 via-sky-50 to-white p-4 flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl overflow-hidden flex items-center justify-center text-white text-xl font-black flex-shrink-0"
                         style="background: linear-gradient(135deg, #31719d, #4d9b97);">
                        @if($managerAvatar)
                            <img src="{{ $managerAvatar }}" alt="{{ $manager?->name }}" class="w-full h-full object-cover">
                        @else
                            {{ mb_substr((string) ($manager?->name ?? '—'), 0, 1) }}
                        @endif
                    </div>
                    <div class="flex-1 min-w-0 text-right">
                        <p class="text-xs text-cyan-700 font-semibold mb-1">الفائز بلقب مدير الشهر</p>
                        <p class="font-black text-slate-800 text-lg truncate">{{ $manager?->name }}</p>
                        <p class="text-sm text-slate-600">{{ $manager?->position_line ?? 'مدير قسم' }}</p>
                        <p class="text-xs text-slate-500 mt-1">قسم {{ $previousMonthBestManager['department']->name }}</p>
                    </div>
                    <div class="text-right sm:text-left">
                        <p class="text-xs text-slate-500">متوسط النقاط</p>
                        <p class="text-lg font-black text-cyan-700">{{ number_format((float) $previousMonthBestManager['avg_final_score'], 2) }}</p>
                    </div>
                </div>
            @else
                <div class="text-center text-slate-500 py-6">
                    {{ $previousMonthPublished ? 'لا يوجد مدير مستوفي شرط 3 من 4 لهذا الشهر.' : 'لا توجد نتائج منشورة للشهر الماضي بعد.' }}
                </div>
            @endif
        </div>
    </div>

    {{-- Confetti burst overlay --}}
    <div x-show="showConfetti" x-transition:enter="transition duration-200" x-transition:leave="transition duration-500 ease-in" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 pointer-events-none flex items-center justify-center">
        <div class="confetti-container" id="confetti-box"></div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 space-y-4">
            <template x-if="fetchError">
                <div class="alert-error animate-slide-up">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p x-text="fetchError"></p>
                </div>
            </template>

            <template x-if="hasVoted">
                <div class="rounded-2xl border border-emerald-300 bg-gradient-to-br from-emerald-50 to-teal-50 p-5 shadow-sm animate-fade-in">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0 shadow">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-emerald-800">✅ تم تسجيل صوتك بنجاح!</h3>
                            <p class="text-sm text-emerald-700 mt-1">شكراً لمساهمتك في اختيار موظف الشهر.</p>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="!hasVoted && !canVote">
                <div class="rounded-2xl border border-amber-300 bg-gradient-to-br from-amber-50 to-yellow-50 p-5 shadow-sm animate-fade-in">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0 shadow">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-amber-800" x-text="statusLabel"></h3>
                            <p class="text-sm text-amber-700 mt-1">يمكنك العودة الشهر القادم داخل النافذة الزمنية المتاحة.</p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Candidate cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="candidateCards.length > 0">
                <template x-for="(candidate, idx) in candidateCards" :key="candidate.id">
                    <button type="button"
                            @click="selectCandidate(candidate.id)"
                            :disabled="!canVote || hasVoted || submitting"
                            class="group text-right relative overflow-hidden rounded-2xl border bg-white p-4 transition-all duration-300"
                            :class="selectedEmployeeId === candidate.id
                                ? 'border-secondary-400 ring-2 ring-secondary-200 shadow-lg shadow-secondary-100 scale-[1.02]'
                                : 'border-slate-200 hover:border-secondary-300 hover:shadow-md hover:-translate-y-0.5 hover:scale-[1.01]'"
                            :style="`animation: slideUp .38s ease ${Math.min(idx * 55, 350)}ms both;`">

                        {{-- Selected glow bg --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-secondary-50 to-teal-50 opacity-0 transition-opacity duration-300 rounded-2xl"
                             :class="selectedEmployeeId === candidate.id ? 'opacity-100' : 'group-hover:opacity-40'"></div>

                        <div class="relative flex items-center gap-3">
                            {{-- Avatar --}}
                            <div class="relative flex-shrink-0">
                                <div class="w-14 h-14 rounded-2xl overflow-hidden flex items-center justify-center text-white text-xl font-black shadow-md transition-transform duration-300"
                                     :class="selectedEmployeeId === candidate.id ? 'scale-110' : ''"
                                     style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                                    <template x-if="candidate.avatar">
                                        <img :src="candidate.avatar" :alt="candidate.name" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!candidate.avatar">
                                        <span x-text="candidate.name.charAt(0)"></span>
                                    </template>
                                </div>
                                {{-- Selected checkmark badge --}}
                                <div class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-secondary-500 text-white flex items-center justify-center transition-all duration-300 shadow"
                                     :class="selectedEmployeeId === candidate.id ? 'opacity-100 scale-100' : 'opacity-0 scale-0'">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-slate-800 truncate text-sm" x-text="candidate.name"></p>
                                <p class="text-xs font-semibold mt-0.5 transition-colors duration-200"
                                   :class="selectedEmployeeId === candidate.id ? 'text-secondary-600' : 'text-slate-500'"
                                              x-text="candidate.position_line || 'موظف'"></p>
                            </div>

                            {{-- Radio circle --}}
                            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all duration-200 flex-shrink-0"
                                 :class="selectedEmployeeId === candidate.id
                                     ? 'border-secondary-500 bg-secondary-500'
                                     : 'border-slate-300 bg-white group-hover:border-secondary-300'">
                                <div class="w-2.5 h-2.5 rounded-full bg-white transition-transform duration-200"
                                     :class="selectedEmployeeId === candidate.id ? 'scale-100' : 'scale-0'"></div>
                            </div>
                        </div>
                    </button>
                </template>
            </div>

            <div x-show="candidateCards.length === 0" class="card p-10 text-center">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                </svg>
                <p class="text-slate-500 font-semibold">لا يوجد مرشحون متاحون حالياً.</p>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            {{-- Confirm panel --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                    <h3 class="text-sm font-bold text-slate-700">تأكيد التصويت</h3>
                    <p class="text-xs text-slate-400 mt-0.5">اختيارك نهائي ولا يمكن تعديله</p>
                </div>
                <div class="p-5 space-y-4">
                    {{-- Selected preview --}}
                    <div x-show="selectedCandidate"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="flex items-center gap-3 p-3 rounded-xl bg-secondary-50 border border-secondary-200">
                        <div class="w-10 h-10 rounded-xl overflow-hidden flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                             style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                            <template x-if="selectedCandidate?.avatar">
                                <img :src="selectedCandidate.avatar" :alt="selectedCandidate?.name" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!selectedCandidate?.avatar">
                                <span x-text="selectedCandidate?.name?.charAt(0) ?? '?'"></span>
                            </template>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-secondary-600 font-semibold">المرشح المختار</p>
                            <p class="text-sm font-bold text-slate-800 truncate" x-text="selectedCandidate?.name"></p>
                            <p class="text-xs text-slate-500" x-text="selectedCandidate?.position_line || 'موظف'"></p>
                        </div>
                    </div>

                    <div x-show="!selectedCandidate" class="text-center py-3 text-sm text-slate-400">
                        لم تختر مرشحاً بعد
                    </div>

                    <button type="button"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl font-bold text-sm text-slate-900 transition-all duration-300 shadow-md"
                            :class="canSubmit
                                ? 'bg-gradient-to-l from-amber-300 to-yellow-300 hover:from-amber-400 hover:to-yellow-400 hover:shadow-lg hover:scale-[1.02] active:scale-[0.98]'
                                : 'bg-slate-200 text-slate-500 cursor-not-allowed'"
                            :disabled="!canSubmit"
                            @click="submitVote()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!submitting">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" x-show="submitting" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="submitting ? 'جاري الإرسال...' : 'تأكيد التصويت'"></span>
                    </button>
                </div>
            </div>

            {{-- Countdown panel --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-4 h-4 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-sm font-bold text-slate-700">نافذة التصويت</h3>
                </div>
                <div class="space-y-2 text-xs text-slate-500">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span>تبدأ: يوم 22 من الشهر السابق</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-400"></span>
                        <span>تنتهي: يوم 21 الساعة 23:59:59</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        <span class="font-semibold">{{ config('app.timezone') }}</span>
                    </div>
                </div>
                <div class="mt-4 px-4 py-3 rounded-xl text-center font-black text-xl tracking-widest"
                     :class="secondsRemaining > 0 ? 'bg-secondary-50 text-secondary-700' : 'bg-red-50 text-red-600'"
                     x-text="countdownLabel"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function employeeMonthVotePage(config) {
    return {
        status: config.initialStatus,
        candidateCards: config.candidates,
        selectedEmployeeId: null,
        submitting: false,
        fetchError: '',
        timer: null,
        showConfetti: false,
        popup: {
            open: false,
            type: 'success',
            title: '',
            message: '',
        },
        popupTimer: null,

        init() {
            this.startCountdown();
            this.timer = setInterval(() => {
                if (this.secondsRemaining > 0) {
                    this.status.seconds_remaining_to_close -= 1;
                }
            }, 1000);
        },

        launchConfetti() {
            this.showConfetti = true;
            const box = document.getElementById('confetti-box');
            if (!box) return;
            box.innerHTML = '';
            const colors = ['#f59e0b','#10b981','#3b82f6','#ec4899','#8b5cf6','#ef4444'];
            for (let i = 0; i < 60; i++) {
                const el = document.createElement('div');
                el.className = 'confetti-piece';
                el.style.cssText = `
                    position:absolute;
                    width:${6 + Math.random()*8}px;
                    height:${6 + Math.random()*8}px;
                    background:${colors[Math.floor(Math.random()*colors.length)]};
                    border-radius:${Math.random()>0.5?'50%':'2px'};
                    left:${20 + Math.random()*60}vw;
                    top:${20 + Math.random()*30}vh;
                    animation: confettiFall ${1.2 + Math.random()*1.5}s ease-out ${Math.random()*0.5}s forwards;
                    opacity:1;
                `;
                box.appendChild(el);
            }
            setTimeout(() => { this.showConfetti = false; }, 2500);
        },

        get hasVoted() {
            return this.status.has_voted === true;
        },

        get canVote() {
            return this.status.can_vote === true;
        },

        get secondsRemaining() {
            return Number(this.status.seconds_remaining_to_close || 0);
        },

        get selectedCandidate() {
            return this.candidateCards.find(c => c.id === this.selectedEmployeeId) || null;
        },

        get canSubmit() {
            return !this.submitting && this.selectedEmployeeId !== null;
        },

        get statusLabel() {
            const labels = {
                ok: 'التصويت متاح الآن',
                already_voted: 'You already voted',
                voting_closed: 'Voting is closed for this month',
                ineligible_voter: 'غير مؤهل للتصويت',
            };
            return labels[this.status.reason] || 'حالة غير معروفة';
        },

        get countdownLabel() {
            const total = this.secondsRemaining;
            if (total <= 0) {
                return 'انتهت نافذة التصويت';
            }

            const days = Math.floor(total / 86400);
            const hours = Math.floor((total % 86400) / 3600);
            const mins = Math.floor((total % 3600) / 60);
            const secs = total % 60;

            const hh = String(hours).padStart(2, '0');
            const mm = String(mins).padStart(2, '0');
            const ss = String(secs).padStart(2, '0');

            return days > 0 ? `${days} يوم ${hh}:${mm}:${ss}` : `${hh}:${mm}:${ss}`;
        },

        selectCandidate(id) {
            if (!this.canVote || this.hasVoted || this.submitting) {
                return;
            }
            this.selectedEmployeeId = id;
        },

        async submitVote() {
            if (this.submitting || this.selectedEmployeeId === null) {
                return;
            }

            if (this.hasVoted) {
                const votedCandidate = this.findCandidateById(this.status.voted_employee_id) || this.selectedCandidate;
                this.showPopup(
                    'warning',
                    'تم التصويت بالفعل',
                    votedCandidate
                        ? `لا يمكن التصويت مرة أخرى. تم التصويت لـ ${votedCandidate.name}.`
                        : 'لا يمكن التصويت مرة أخرى في نفس الدورة.'
                );
                return;
            }

            if (!this.canVote) {
                this.showPopup('warning', 'التصويت غير متاح', this.statusLabel);
                return;
            }

            this.submitting = true;
            this.fetchError = '';
            const chosenCandidate = this.selectedCandidate;

            try {
                const response = await fetch(config.voteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: JSON.stringify({ voted_employee_id: this.selectedEmployeeId }),
                });

                const data = await response.json();

                if (!response.ok) {
                    this.fetchError = data.message || 'حدث خطأ أثناء إرسال التصويت.';
                    return;
                }

                this.status.has_voted = true;
                this.status.can_vote = false;
                this.status.reason = 'already_voted';
                this.status.voted_employee_id = data.voted_employee_id;
                this.status.seconds_remaining_to_close = data.seconds_remaining_to_close;

                const votedCandidate = this.findCandidateById(data.voted_employee_id) || chosenCandidate;
                if (data.status === 'already_voted') {
                    this.showPopup(
                        'warning',
                        'تم التصويت بالفعل',
                        votedCandidate
                            ? `لا يمكن التصويت مرة أخرى. تم التصويت لـ ${votedCandidate.name}.`
                            : 'لا يمكن التصويت مرة أخرى في نفس الدورة.'
                    );
                } else {
                    this.launchConfetti();
                    this.showPopup(
                        'success',
                        '🎉 شكراً على تصويتك!',
                        votedCandidate
                            ? `تم التصويت لـ ${votedCandidate.name} بنجاح.`
                            : 'تم حفظ التصويت بنجاح.'
                    );
                }
            } catch (e) {
                this.fetchError = 'تعذر الاتصال بالخادم. حاول مرة أخرى.';
            } finally {
                this.submitting = false;
            }
        },

        findCandidateById(id) {
            if (id === null || id === undefined) {
                return null;
            }

            return this.candidateCards.find(c => Number(c.id) === Number(id)) || null;
        },

        showPopup(type, title, message) {
            this.popup.type = type;
            this.popup.title = title;
            this.popup.message = message;
            this.popup.open = true;

            if (this.popupTimer) {
                clearTimeout(this.popupTimer);
            }

            this.popupTimer = setTimeout(() => {
                this.popup.open = false;
            }, 2500);
        },

        closePopup() {
            this.popup.open = false;
            if (this.popupTimer) {
                clearTimeout(this.popupTimer);
                this.popupTimer = null;
            }
        },

        startCountdown() {
            if (this.secondsRemaining <= 0 && this.status.reason === 'ok') {
                this.status.reason = 'voting_closed';
                this.status.can_vote = false;

            }
        }
    }
}
</script>

@push('styles')
<style>
@keyframes confettiFall {
    0%   { transform: translateY(0) rotate(0deg) scale(1); opacity: 1; }
    80%  { opacity: 0.8; }
    100% { transform: translateY(200px) rotate(720deg) scale(0.4); opacity: 0; }
}
.confetti-container { position: fixed; inset: 0; pointer-events: none; z-index: 9999; }
</style>
@endpush
