@extends('layouts.app')

@section('title', 'لوحة موظف الشهر')
@section('page-title', 'لوحة موظف الشهر')
@section('page-subtitle', 'تحليل شامل للنتائج وفق معادلة المهام الجديدة')

@section('content')
@php
    $monthLabel = \Carbon\Carbon::create($year, $month, 1)->locale('ar')->isoFormat('MMMM YYYY');
    $pointCaps = $scoring['points_caps'] ?? [
        'tasks' => 40,
        'vote' => 25,
        'work_hours' => 20,
        'punctuality' => 15,
    ];

    $formatMinutes = function (int $minutes): string {
        $h = (int) floor($minutes / 60);
        $m = $minutes % 60;
        return $h . ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    };
@endphp

<div class="space-y-5" x-data="{ showHistoryDetails: false, showExplain: true, showAllVotes: false, showAllFinalRanking: false, showAllExplainRows: false, showAllHistoryMonth: false, showAllHistoryWinners: false, showAllTaskPerformance: false, showAllWorkHours: false, showAllPunctuality: false }">

    <div class="card p-0 overflow-hidden relative animate-fade-in">
        <div class="absolute inset-0 opacity-95"
             style="background: radial-gradient(circle at 85% 20%, rgba(231,197,57,.24), transparent 40%), radial-gradient(circle at 10% 85%, rgba(77,155,151,.26), transparent 42%), linear-gradient(140deg, #2e6d98 0%, #2f7c77 100%);"></div>

        <div class="relative p-6 sm:p-7 text-white">
            <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-white/70 mb-2">Employee Of The Month</p>
                    <h2 class="text-2xl sm:text-3xl font-black">النتائج النهائية لشهر {{ $monthLabel }}</h2>
                    <p class="text-sm text-white/80 mt-2">المعادلة الحالية بالنقاط: Tasks {{ $pointCaps['tasks'] }} + Vote {{ $pointCaps['vote'] }} + Work Hours {{ $pointCaps['work_hours'] }} + Punctuality {{ $pointCaps['punctuality'] }}</p>
                </div>

                <div class="w-full xl:w-auto space-y-2">
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 gap-2 xl:min-w-[280px]">
                        <select name="month" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $m, 1)->locale('ar')->isoFormat('MMMM') }}
                                </option>
                            @endforeach
                        </select>
                        <select name="year" onchange="this.form.submit()" class="form-input !h-11 !min-h-0 !py-1.5 !px-4 !text-sm !bg-white/95 !border-white/30 !rounded-xl">
                            @foreach(range(now()->year, now()->year - 4) as $y)
                                <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </form>

                    <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                        <a href="{{ route('employee-of-month.admin.export', ['month' => $month, 'year' => $year]) }}" class="btn-outline btn-sm bg-white/95 !h-9 !text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-8m0 8l-3-3m3 3l3-3M5 20h14"/>
                            </svg>
                            تصدير Excel
                        </a>

                        <form method="POST" action="{{ route('employee-of-month.admin.finalize') }}">
                            @csrf
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <button type="submit" class="btn-primary btn-sm !h-9 !text-xs">اعتماد النتائج</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div class="card p-4 animate-slide-up" style="animation-delay:40ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">إجمالي الأصوات</p>
            <p class="text-2xl font-black text-slate-800 mt-1">{{ $metrics['total_valid_votes'] }}</p>
        </div>
        <div class="card p-4 animate-slide-up" style="animation-delay:80ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">عدد المصوّتين</p>
            <p class="text-2xl font-black text-slate-800 mt-1">{{ $metrics['voters_count'] }}</p>
        </div>
        <div class="card p-4 animate-slide-up" style="animation-delay:120ms; animation-fill-mode:both;">
            <p class="text-xs text-slate-500">عدد المرشحين</p>
            <p class="text-2xl font-black text-slate-800 mt-1">{{ collect($metrics['rows'])->count() }}</p>
        </div>
    </div>

    <div class="card overflow-hidden animate-slide-up" style="animation-delay:140ms; animation-fill-mode:both;">
        <div class="card-header flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            <h3>المراكز الثلاثة الأولى</h3>
        </div>
        <div class="p-5 flex flex-col gap-3" dir="rtl">
            @php
                $adminRankConfig = [
                    0 => [
                        'border'   => 'border-amber-400',
                        'ring'     => 'ring-2 ring-amber-200',
                        'gradient' => 'from-amber-50 via-yellow-50 to-white',
                        'medal'    => asset('images/medal-first.png'),
                        'label'    => 'المركز الأول',
                        'text'     => 'text-amber-700',
                    ],
                    1 => [
                        'border'   => 'border-slate-400',
                        'ring'     => 'ring-2 ring-slate-200',
                        'gradient' => 'from-slate-50 via-gray-50 to-white',
                        'medal'    => asset('images/medal-second.png'),
                        'label'    => 'المركز الثاني',
                        'text'     => 'text-slate-600',
                    ],
                    2 => [
                        'border'   => 'border-amber-700/50',
                        'ring'     => 'ring-2 ring-amber-100',
                        'gradient' => 'from-orange-50 via-amber-50 to-white',
                        'medal'    => asset('images/medal-third.png'),
                        'label'    => 'المركز الثالث',
                        'text'     => 'text-amber-800',
                    ],
                ];
            @endphp
            @forelse($topThreeRanking as $idx => $row)
                @php
                    $emp   = $row['employee'];
                    $cfg   = $adminRankConfig[$idx] ?? $adminRankConfig[2];
                    $avatarUrl = $emp->user?->profile?->avatar_path
                        ? route('media.avatar', ['path' => $emp->user->profile->avatar_path])
                        : null;
                @endphp

                {{-- Horizontal card: medal | info | avatar --}}
                <div class="rounded-2xl border {{ $cfg['border'] }} {{ $cfg['ring'] }} bg-gradient-to-l {{ $cfg['gradient'] }} px-4 py-3 flex items-center gap-4 shadow-sm"
                     style="animation: slideUp .4s ease {{ $idx * 80 }}ms both;">

                    {{-- Medal on the left, bigger --}}
                    <img src="{{ $cfg['medal'] }}" alt="medal"
                         class="w-16 h-16 object-contain drop-shadow-lg flex-shrink-0">

                    {{-- Info block: rank label (black) + name + job + score --}}
                    <div class="flex-1 min-w-0 text-right">
                        <p class="text-xs font-bold text-slate-900 mb-0.5">{{ $cfg['label'] }}</p>
                        <p class="font-bold text-slate-800 truncate">{{ $emp->name }}</p>
                        <p class="text-xs {{ $cfg['text'] }} font-semibold">
                            {{ $emp->job_title?->label() ?? 'موظف' }}
                        </p>
                        <p class="text-sm font-black {{ $cfg['text'] }} mt-0.5">
                            {{ number_format($row['final_score'], 1) }}
                            <span class="text-xs font-medium opacity-70">نقطة</span>
                        </p>
                    </div>

                    {{-- Avatar on the far right --}}
                    <div class="w-12 h-12 rounded-xl overflow-hidden flex items-center justify-center text-white text-base font-black flex-shrink-0 shadow-md"
                         style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $emp->name }}" class="w-full h-full object-cover">
                        @else
                            {{ mb_substr($emp->name, 0, 1) }}
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-slate-500 py-8">لا توجد بيانات كافية لعرض أول 3 مراكز.</div>
            @endforelse
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 card overflow-hidden animate-slide-up">
            <div class="card-header flex items-center justify-between gap-3">
                <h3>نتائج التصويت</h3>
                @if($voteRanking->count() > 5)
                    <button type="button" class="btn-ghost btn-sm !text-white !bg-slate-800/40 hover:!bg-slate-800/60" @click="showAllVotes = !showAllVotes">
                        <span x-text="showAllVotes ? 'عرض أول 5 فقط' : 'عرض الباقي'"></span>
                    </button>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الموظف</th>
                            <th class="text-center">عدد الأصوات</th>
                            <th class="text-center">نقاط التصويت</th>
                            <th class="text-center">النسبة</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($voteRanking as $idx => $row)
                        @php
                            $emp = $row['employee'];
                            $percent = $metrics['total_valid_votes'] > 0 ? round(($row['votes_count'] / $metrics['total_valid_votes']) * 100, 1) : 0;
                            $points = $pointsByEmployee[$emp->id] ?? [];
                            $votePoints = (float) ($points['vote_points'] ?? 0);
                            $avatarUrl = $emp->user?->profile?->avatar_path
                                ? route('media.avatar', ['path' => $emp->user->profile->avatar_path])
                                : null;
                        @endphp
                        <tr x-show="{{ $idx < 5 ? 'true' : 'showAllVotes' }}" x-transition.opacity.duration.200ms>
                            <td class="font-semibold text-slate-500">{{ $idx + 1 }}</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl overflow-hidden flex items-center justify-center text-white text-xs font-bold"
                                         style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                                        @if($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="{{ $emp->name }}" class="w-full h-full object-cover">
                                        @else
                                            {{ mb_substr($emp->name, 0, 1) }}
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800 text-sm">{{ $emp->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $emp->ac_no }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center"><span class="badge-blue">{{ $row['votes_count'] }}</span></td>
                            <td class="text-center"><span class="text-xs font-semibold text-slate-700">{{ number_format($votePoints, 2) }} / {{ (int) $pointCaps['vote'] }}</span></td>
                            <td class="text-center"><span class="text-xs font-bold text-secondary-700">{{ $percent }}%</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-slate-500 py-8">لا توجد أصوات لهذا الشهر بعد.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4 animate-slide-up" style="animation-delay:70ms; animation-fill-mode:both;">
            <div class="card overflow-hidden">
                <div class="card-header flex items-center justify-between gap-2">
                    <h3>أعلى ساعات عمل</h3>
                    @if($workHoursRanking->count() > 5)
                        <button type="button" class="btn-ghost btn-sm !text-white !bg-slate-800/40 hover:!bg-slate-800/60" @click="showAllWorkHours = !showAllWorkHours">
                            <span x-text="showAllWorkHours ? 'عرض أول 5 فقط' : 'عرض الباقي'"></span>
                        </button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الموظف</th>
                                <th class="text-center">ساعات العمل</th>
                                <th class="text-center">نقاط ساعات العمل</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($workHoursRanking as $idx => $row)
                            @php
                                $points = $pointsByEmployee[$row['employee']->id] ?? [];
                                $workHoursPoints = (float) ($points['work_hours_points'] ?? 0);
                            @endphp
                            <tr x-show="{{ $idx < 5 ? 'true' : 'showAllWorkHours' }}" x-transition.opacity.duration.200ms>
                                <td class="font-semibold text-slate-500">{{ $idx + 1 }}</td>
                                <td>
                                    <p class="font-semibold text-slate-800 text-sm">{{ $row['employee']->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $row['employee']->ac_no }}</p>
                                </td>
                                <td class="text-center"><span class="badge-blue">{{ $formatMinutes((int) $row['work_minutes']) }}</span></td>
                                <td class="text-center text-xs font-semibold text-slate-700">{{ number_format($workHoursPoints, 2) }} / {{ (int) $pointCaps['work_hours'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-slate-500 py-8">لا توجد بيانات ساعات عمل.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="card-header flex items-center justify-between gap-2">
                    <h3>الأكثر انضباطا (Punctuality)</h3>
                    @if($punctualityRanking->count() > 5)
                        <button type="button" class="btn-ghost btn-sm !text-white !bg-slate-800/40 hover:!bg-slate-800/60" @click="showAllPunctuality = !showAllPunctuality">
                            <span x-text="showAllPunctuality ? 'عرض أول 5 فقط' : 'عرض الباقي'"></span>
                        </button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الموظف</th>
                                <th class="text-center">دقائق التأخير</th>
                                <th class="text-center">نقاط الانضباط</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($punctualityRanking as $idx => $row)
                            @php
                                $points = $pointsByEmployee[$row['employee']->id] ?? [];
                                $punctualityPoints = (float) ($points['punctuality_points'] ?? 0);
                            @endphp
                            <tr x-show="{{ $idx < 5 ? 'true' : 'showAllPunctuality' }}" x-transition.opacity.duration.200ms>
                                <td class="font-semibold text-slate-500">{{ $idx + 1 }}</td>
                                <td>
                                    <p class="font-semibold text-slate-800 text-sm">{{ $row['employee']->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $row['employee']->ac_no }}</p>
                                </td>
                                <td class="text-center"><span class="badge-success">{{ (int) $row['late_minutes'] }} دقيقة</span></td>
                                <td class="text-center text-xs font-semibold text-slate-700">{{ number_format($punctualityPoints, 2) }} / {{ (int) $pointCaps['punctuality'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-slate-500 py-8">لا توجد بيانات انضباط.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card overflow-hidden animate-slide-up" style="animation-delay:100ms; animation-fill-mode:both;">
        <div class="card-header flex items-center justify-between gap-3">
            <h3>إنجاز المهام لكل موظف</h3>
            @if(collect($metrics['rows'])->count() > 5)
                <button type="button" class="btn-ghost btn-sm !text-white !bg-slate-800/40 hover:!bg-slate-800/60" @click="showAllTaskPerformance = !showAllTaskPerformance">
                    <span x-text="showAllTaskPerformance ? 'عرض أول 5 فقط' : 'عرض الباقي'"></span>
                </button>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الموظف</th>
                        <th class="text-center">المهام المسندة</th>
                        <th class="text-center">المهام المقيمة</th>
                        <th class="text-center">نسبة الإنجاز</th>
                        <th class="text-center">متوسط تقييم المهام</th>
                        <th class="text-center">نقاط المهام</th>
                    </tr>
                </thead>
                <tbody>
                @php
                    $taskPerformanceRows = collect($metrics['rows'])
                        ->map(function (array $row) {
                            $assigned = (int) ($row['assigned_tasks_count'] ?? 0);
                            $evaluated = (int) ($row['evaluated_tasks_count'] ?? 0);
                            $row['task_achievement_ratio'] = $assigned > 0 ? round(($evaluated / $assigned) * 100, 2) : 0.0;
                            $row['task_avg_score'] = $row['task_score_raw'] !== null ? (float) $row['task_score_raw'] : null;

                            return $row;
                        })
                        ->sort(function (array $a, array $b) {
                            $aAvg = $a['task_avg_score'];
                            $bAvg = $b['task_avg_score'];

                            if ($aAvg === null && $bAvg === null) {
                                return ($b['task_achievement_ratio'] <=> $a['task_achievement_ratio']);
                            }

                            if ($aAvg === null) {
                                return 1;
                            }

                            if ($bAvg === null) {
                                return -1;
                            }

                            $avgCompare = $bAvg <=> $aAvg;

                            return $avgCompare !== 0
                                ? $avgCompare
                                : ($b['task_achievement_ratio'] <=> $a['task_achievement_ratio']);
                        })
                        ->values();
                @endphp
                @forelse($taskPerformanceRows as $idx => $row)
                    @php
                        $assigned = (int) ($row['assigned_tasks_count'] ?? 0);
                        $evaluated = (int) ($row['evaluated_tasks_count'] ?? 0);
                        $achievement = (float) ($row['task_achievement_ratio'] ?? 0);
                        $avgTask = $row['task_score_raw'] !== null ? (float) $row['task_score_raw'] : null;
                        $taskPoints = (float) (($pointsByEmployee[$row['employee']->id]['task_points'] ?? 0));
                    @endphp
                    <tr x-show="{{ $idx < 5 ? 'true' : 'showAllTaskPerformance' }}" x-transition.opacity.duration.200ms>
                        <td class="font-semibold text-slate-500">{{ $idx + 1 }}</td>
                        <td>
                            <p class="font-semibold text-slate-800 text-sm">{{ $row['employee']->name }}</p>
                            <p class="text-xs text-slate-400">{{ $row['employee']->ac_no }}</p>
                        </td>
                        <td class="text-center text-xs">{{ $assigned }}</td>
                        <td class="text-center text-xs">{{ $evaluated }}</td>
                        <td class="text-center"><span class="badge-blue">{{ number_format($achievement, 2) }}%</span></td>
                        <td class="text-center text-xs font-semibold {{ $avgTask === null ? 'text-slate-400' : 'text-emerald-700' }}">
                            {{ $avgTask === null ? '—' : number_format($avgTask, 2) }}
                        </td>
                        <td class="text-center text-xs font-semibold text-slate-700">{{ number_format($taskPoints, 2) }} / {{ (int) $pointCaps['tasks'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-500 py-8">لا توجد بيانات مهام لعرض الإنجاز.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden animate-slide-up" style="animation-delay:120ms; animation-fill-mode:both;">
        <div class="card-header flex items-center justify-between gap-3">
            <h3>الترتيب النهائي حسب المعادلة</h3>
            @if(collect($scoring['scored_rows'])->count() > 5)
                <button type="button" class="btn-ghost btn-sm !text-white !bg-slate-800/40 hover:!bg-slate-800/60" @click="showAllFinalRanking = !showAllFinalRanking">
                    <span x-text="showAllFinalRanking ? 'عرض أول 5 فقط' : 'عرض الباقي'"></span>
                </button>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الموظف</th>
                        <th class="text-center">الإجمالي / 100</th>
                        <th class="text-center">نقاط المهام / {{ (int) $pointCaps['tasks'] }}</th>
                        <th class="text-center">نقاط التصويت / {{ (int) $pointCaps['vote'] }}</th>
                        <th class="text-center">نقاط ساعات العمل / {{ (int) $pointCaps['work_hours'] }}</th>
                        <th class="text-center">نقاط الانضباط / {{ (int) $pointCaps['punctuality'] }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($scoring['scored_rows'] as $idx => $row)
                    @php
                        $emp = $row['employee'];
                        $b = $row['breakdown'];
                        $avatarUrl = $emp->user?->profile?->avatar_path
                            ? route('media.avatar', ['path' => $emp->user->profile->avatar_path])
                            : null;
                        $isFirst = $firstPlaceEmployeeId !== null && (int) $emp->id === (int) $firstPlaceEmployeeId;
                        $taskPoints = (float) ($b['task_points'] ?? $b['task_score'] ?? 0);
                        $votePoints = (float) ($b['vote_points'] ?? $b['vote_score'] ?? 0);
                        $workHoursPoints = (float) ($b['work_hours_points'] ?? $b['work_hours_score'] ?? 0);
                        $punctualityPoints = (float) ($b['punctuality_points'] ?? $b['punctuality_score'] ?? 0);
                    @endphp
                    <tr x-show="{{ $idx < 5 ? 'true' : 'showAllFinalRanking' }}" x-transition.opacity.duration.200ms>
                        <td class="font-semibold text-slate-500">{{ $idx + 1 }}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl overflow-hidden flex items-center justify-center text-white text-xs font-bold {{ $isFirst ? 'border-4 border-amber-400 ring-2 ring-amber-200' : '' }}"
                                     style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                                    @if($avatarUrl)
                                        <img src="{{ $avatarUrl }}" alt="{{ $emp->name }}" class="w-full h-full object-cover">
                                    @else
                                        {{ mb_substr($emp->name, 0, 1) }}
                                    @endif
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800 text-sm">{{ $emp->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $emp->ac_no }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-center"><span class="badge-success">{{ number_format((float) $row['final_score'], 2) }}</span></td>
                        <td class="text-center text-xs font-semibold text-emerald-700">{{ number_format($taskPoints, 2) }}</td>
                        <td class="text-center text-xs">{{ number_format($votePoints, 2) }}</td>
                        <td class="text-center text-xs">{{ number_format($workHoursPoints, 2) }}</td>
                        <td class="text-center text-xs">{{ number_format($punctualityPoints, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-slate-500 py-8">لا توجد بيانات كافية لحساب الترتيب النهائي.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden animate-slide-up" style="animation-delay:150ms; animation-fill-mode:both;">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between gap-3">
            <h3 class="font-bold text-slate-800">Explain Score</h3>
            <div class="flex items-center gap-2">
                @if($explainRows->count() > 5)
                    <button type="button" class="btn-ghost btn-sm !text-white !bg-slate-800/40 hover:!bg-slate-800/60" @click="showAllExplainRows = !showAllExplainRows">
                        <span x-text="showAllExplainRows ? 'عرض أول 5 فقط' : 'عرض الباقي'"></span>
                    </button>
                @endif
                <button type="button" class="btn-ghost btn-sm" @click="showExplain = !showExplain">
                    <span x-text="showExplain ? 'إخفاء' : 'عرض'"></span>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto" x-show="showExplain" x-transition>
            <table class="data-table">
                <thead>
                <tr>
                    <th>الموظف</th>
                    <th class="text-center">Final</th>
                    <th class="text-center">Task</th>
                    <th class="text-center">Votes</th>
                    <th class="text-center">Work Min</th>
                    <th class="text-center">Late Min</th>
                    <th class="text-center">Assigned/Evaluated</th>
                </tr>
                </thead>
                <tbody>
                @foreach($explainRows as $idx => $row)
                    @php
                        $raw = $row['raw_inputs'];
                    @endphp
                    <tr x-show="{{ $idx < 5 ? 'true' : 'showAllExplainRows' }}" x-transition.opacity.duration.200ms>
                        <td>
                            <p class="font-semibold text-slate-800 text-sm">{{ $row['employee']->name }}</p>
                            <p class="text-xs text-slate-400">{{ $row['employee']->ac_no }}</p>
                        </td>
                        <td class="text-center"><span class="badge-success">{{ number_format($row['final_score'], 2) }}</span></td>
                        <td class="text-center text-xs">{{ number_format($row['task_score'], 1) }}</td>
                        <td class="text-center text-xs">{{ (int) ($raw['votes_count'] ?? 0) }}</td>
                        <td class="text-center text-xs">{{ (int) ($raw['work_minutes'] ?? 0) }}</td>
                        <td class="text-center text-xs">{{ (int) ($raw['late_minutes'] ?? 0) }}</td>
                        <td class="text-center text-xs">{{ (int) ($raw['assigned_tasks_count'] ?? 0) }} / {{ (int) ($raw['evaluated_tasks_count'] ?? 0) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden animate-slide-up" style="animation-delay:180ms; animation-fill-mode:both;">
        <div class="card-header">
            <h3>History النتائج الشهرية</h3>
        </div>

        <div class="p-4 border-b border-slate-100 flex items-center justify-between gap-3">
            <button type="button" class="btn-ghost btn-sm" @click="showHistoryDetails = !showHistoryDetails">
                <span x-text="showHistoryDetails ? 'إخفاء تفاصيل الشهر الحالي' : 'عرض تفاصيل الشهر الحالي'"></span>
            </button>
            @if($historyForSelectedMonth->count() > 5)
                <button type="button" class="btn-ghost btn-sm !text-white !bg-slate-800/40 hover:!bg-slate-800/60" @click="showAllHistoryMonth = !showAllHistoryMonth" x-show="showHistoryDetails" x-transition>
                    <span x-text="showAllHistoryMonth ? 'عرض أول 5 فقط' : 'عرض الباقي'"></span>
                </button>
            @endif
        </div>

        <div class="overflow-x-auto" x-show="showHistoryDetails" x-transition>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الموظف</th>
                        <th class="text-center">Final Score</th>
                        <!-- <th class="text-center">Formula</th> -->
                        <th class="text-center">Generated At</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($historyForSelectedMonth as $idx => $row)
                    <tr x-show="{{ $idx < 5 ? 'true' : 'showAllHistoryMonth' }}" x-transition.opacity.duration.200ms>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <p class="font-semibold text-slate-800 text-sm">{{ $row->employee?->name }}</p>
                            <p class="text-xs text-slate-400">{{ $row->employee?->ac_no }}</p>
                        </td>
                        <td class="text-center"><span class="badge-success">{{ number_format((float) $row->final_score, 2) }}</span></td>
                        <!-- <td class="text-center text-xs">{{ $row->formula_version }}</td> -->
                        <td class="text-center text-xs">{{ $row->generated_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-slate-500 py-8">لا يوجد History محفوظ لهذا الشهر حتى الآن.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="overflow-x-auto border-t border-slate-100">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>الشهر</th>
                        <th>الفائز</th>
                        <th class="text-center">Final Score</th>
                        <!-- <th class="text-center">Formula</th> -->
                        <th class="text-center">Generated At</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($historyTopWinners as $idx => $winner)
                    <tr x-show="{{ $idx < 5 ? 'true' : 'showAllHistoryWinners' }}" x-transition.opacity.duration.200ms>
                        <td class="text-sm text-slate-600">{{ \Carbon\Carbon::create($winner->year, $winner->month, 1)->locale('ar')->isoFormat('MMMM YYYY') }}</td>
                        <td>
                            <p class="font-semibold text-slate-800 text-sm">{{ $winner->employee?->name }}</p>
                            <p class="text-xs text-slate-400">{{ $winner->employee?->ac_no }}</p>
                        </td>
                        <td class="text-center"><span class="badge-success">{{ number_format((float) $winner->final_score, 2) }}</span></td>
                        <!-- <td class="text-center text-xs">{{ $winner->formula_version }}</td> -->
                        <td class="text-center text-xs">{{ $winner->generated_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-slate-500 py-8">لا توجد نتائج تاريخية بعد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($historyTopWinners->count() > 5)
            <div class="p-4 border-t border-slate-100">
                <button type="button" class="btn-ghost btn-sm !text-white !bg-slate-800/40 hover:!bg-slate-800/60" @click="showAllHistoryWinners = !showAllHistoryWinners">
                    <span x-text="showAllHistoryWinners ? 'عرض أول 5 فقط' : 'عرض الباقي'"></span>
                </button>
            </div>
        @endif
    </div>
</div>
@endsection
