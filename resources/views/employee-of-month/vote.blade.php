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
            'job_title_label' => $c->job_title?->label(),
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
        <div class="card-header">
            <h3>أوائل الشهر الماضي - {{ $previousMonthLabel }}</h3>
        </div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            @forelse($previousMonthTopThree as $idx => $result)
                @php
                    $emp = $result->employee;
                    $avatarUrl = $emp?->user?->profile?->avatar_path
                        ? route('media.avatar', ['path' => $emp->user->profile->avatar_path])
                        : null;
                    $isTitleHolder = (int) $titleHolderEmployeeId === (int) $result->employee_id;
                @endphp
                <div class="rounded-2xl border p-4 bg-white {{ $isTitleHolder ? 'border-amber-400 ring-2 ring-amber-200' : 'border-slate-200' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl overflow-hidden flex items-center justify-center text-white text-sm font-bold"
                             style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                            @if($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ $emp?->name }}" class="w-full h-full object-cover">
                            @else
                                {{ mb_substr((string) ($emp?->name ?? '—'), 0, 1) }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-slate-500">المركز {{ $idx + 1 }}</p>
                            <p class="font-semibold text-slate-800 text-sm truncate">{{ $emp?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ $emp?->ac_no ?? '—' }}</p>
                        </div>
                    </div>

                    <div class="mt-3 text-xs text-slate-600">
                        <p>النقاط: <span class="font-bold text-emerald-700">{{ number_format((float) $result->final_score, 2) }}/100</span></p>
                        @if($isTitleHolder)
                            <p class="text-amber-600 font-semibold mt-1">حامل اللقب الحالي</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="md:col-span-3 text-center text-slate-500 py-5">لا توجد نتائج معتمدة للشهر الماضي حتى الآن.</div>
            @endforelse
        </div>
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
                <div class="card p-5 border-emerald-200 bg-emerald-50/70 animate-fade-in">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-emerald-800">You already voted</h3>
                            <p class="text-sm text-emerald-700 mt-1">تم قفل التصويت لهذا الشهر بنجاح. شكرا لمساهمتك.</p>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="!hasVoted && !canVote">
                <div class="card p-5 border-amber-200 bg-amber-50/70 animate-fade-in">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-amber-800" x-text="statusLabel"></h3>
                            <p class="text-sm text-amber-700 mt-1">يمكنك العودة الشهر القادم للتصويت داخل النافذة الزمنية المتاحة.</p>
                        </div>
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="candidateCards.length > 0">
                <template x-for="(candidate, idx) in candidateCards" :key="candidate.id">
                    <button type="button"
                            @click="selectCandidate(candidate.id)"
                            :disabled="!canVote || hasVoted || submitting"
                            class="group text-right card p-4 transition-all duration-300 hover:-translate-y-1"
                            :class="selectedEmployeeId === candidate.id
                                ? 'ring-2 ring-secondary-400 border-secondary-300 bg-secondary-50 shadow-lg'
                                : 'hover:shadow-md border-slate-200'"
                            :style="`animation: slideUp .35s ease ${Math.min(idx * 55, 300)}ms both;`">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-2xl overflow-hidden flex items-center justify-center text-white text-xl font-black flex-shrink-0"
                                 style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                                <template x-if="candidate.avatar">
                                    <img :src="candidate.avatar" :alt="candidate.name" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!candidate.avatar">
                                    <span x-text="candidate.name.charAt(0)"></span>
                                </template>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-slate-800 truncate" x-text="candidate.name"></p>
                                <p class="text-xs text-slate-500 mt-0.5" x-text="candidate.job_title_label || 'موظف'"></p>
                                <p class="text-[11px] text-slate-400 mt-1 font-mono" x-text="candidate.ac_no"></p>
                            </div>
                            <div class="w-5 h-5 rounded-full border-2 transition"
                                 :class="selectedEmployeeId === candidate.id ? 'border-secondary-500 bg-secondary-500' : 'border-slate-300 bg-white'">
                            </div>
                        </div>
                    </button>
                </template>
            </div>

            <div x-show="candidateCards.length === 0" class="card p-8 text-center">
                <p class="text-slate-600 font-semibold">لا يوجد مرشحون متاحون حاليا.</p>
            </div>
        </div>

        <div class="space-y-4">
            <div class="card p-5">
                <h3 class="text-sm font-bold text-slate-700 mb-3">تأكيد التصويت</h3>
                <p class="text-xs text-slate-500 leading-6">اختيارك نهائي لهذا الشهر، ولا يمكن التعديل أو الحذف بعد الضغط على زر التصويت.</p>

                <div class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-200" x-show="selectedCandidate">
                    <p class="text-xs text-slate-500 mb-1">المرشح المختار</p>
                    <p class="text-sm font-bold text-slate-800" x-text="selectedCandidate?.name"></p>
                    <p class="text-xs text-slate-500 mt-0.5" x-text="selectedCandidate?.job_title_label || 'موظف'"></p>
                </div>

                <button type="button"
                        class="btn-primary btn-lg w-full justify-center mt-4"
                        :disabled="!canSubmit"
                        @click="submitVote()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!submitting">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" x-show="submitting">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span x-text="submitting ? 'جاري إرسال التصويت...' : 'تأكيد التصويت'"></span>
                </button>
            </div>

            <div class="card p-5">
                <h3 class="text-sm font-bold text-slate-700 mb-3">نافذة التصويت</h3>
                <div class="space-y-2 text-xs text-slate-600">
                    <p>البداية: يوم 22 من الشهر السابق</p>
                    <p>النهاية: يوم 21 من الشهر الحالي الساعة 23:59:59</p>
                    <p class="font-semibold text-slate-700">التوقيت: {{ config('app.timezone') }}</p>
                </div>
                <div class="mt-3 text-sm font-bold" :class="secondsRemaining > 0 ? 'text-secondary-700' : 'text-red-600'" x-text="countdownLabel"></div>
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
                    this.showPopup(
                        'success',
                        'شكرا على التصويت',
                        votedCandidate
                            ? `تم التصويت لـ ${votedCandidate.name}.`
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
@endpush
