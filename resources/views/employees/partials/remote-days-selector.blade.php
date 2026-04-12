@php
    $initialRemoteDates = collect($selectedRemoteDates ?? old('remote_allowed_dates', []))
        ->filter(fn ($date) => is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($date)) === 1)
        ->map(fn ($date) => trim($date))
        ->unique()
        ->values()
        ->all();
@endphp

<div id="remote-days-section" class="space-y-3 {{ $isRemoteWorker ? '' : 'hidden' }}">
    <div class="flex items-center gap-2 px-1">
        <div class="h-px flex-1 bg-slate-100"></div>
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">تقويم أيام الريموت</span>
        <div class="h-px flex-1 bg-slate-100"></div>
    </div>

    <div class="remote-days-shell" data-remote-days-root data-initial-dates='@json($initialRemoteDates)' data-max-days="62">
        <div class="remote-days-head">
            <div>
                <p class="remote-days-title">اختر الأيام المسموح بها للريموت</p>
                <p class="remote-days-subtitle">عرض شهري واضح مثل التقويم. لا يمكن اختيار يوم الجمعة أو أي يوم ماضٍ.</p>
            </div>

            <div class="remote-days-nav">
                <button type="button" class="btn-ghost btn-sm" data-remote-month-prev>السابق</button>
                <input id="remote-month-picker" type="month" class="form-input remote-month-input" aria-label="اختر الشهر">
                <button type="button" class="btn-ghost btn-sm" data-remote-month-next>التالي</button>
            </div>
        </div>

        <div class="remote-days-actions">
            <button type="button" class="btn-ghost btn-sm" data-remote-month-select-workdays>تحديد أيام الشهر (عدا الجمعة)</button>
            <button type="button" class="btn-ghost btn-sm" data-remote-month-clear>مسح أيام هذا الشهر</button>
            <button type="button" class="btn-ghost btn-sm" data-remote-clear-all>مسح كل الأيام</button>
            <span class="remote-days-counter" data-remote-days-counter></span>
        </div>

        <div class="remote-calendar-wrap">
            <div class="remote-calendar-top">
                <p class="remote-calendar-month" data-remote-days-grid-title>Loading...</p>
                <p class="remote-calendar-note">Fri: غير متاح</p>
            </div>

            <div class="remote-calendar-weekdays" aria-hidden="true">
                <span>Sun</span>
                <span>Mon</span>
                <span>Tue</span>
                <span>Wed</span>
                <span>Thu</span>
                <span class="weekday-friday">Fri</span>
                <span>Sat</span>
            </div>

            <div class="remote-calendar-grid" data-remote-days-grid></div>

            <div class="remote-calendar-legend">
                <span><i class="dot dot-selected"></i> يوم مختار</span>
                <span><i class="dot dot-today"></i> اليوم الحالي</span>
                <span><i class="dot dot-disabled"></i> يوم غير متاح</span>
            </div>
        </div>

        <div class="remote-selected-wrap">
            <p class="remote-selected-label">الأيام المختارة</p>
            <div class="remote-selected-chips" data-remote-days-selected></div>
            <div data-remote-days-hidden-inputs></div>
            <p class="remote-selected-helper" data-remote-days-helper></p>
        </div>

        @error('remote_allowed_dates')
            <p class="form-error">{{ $message }}</p>
        @enderror
        @error('remote_allowed_dates.*')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
</div>

@push('styles')
<style>
.remote-days-shell {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #ffffff;
    padding: 16px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.remote-days-head {
    display: flex;
    gap: 10px;
    justify-content: space-between;
    align-items: flex-end;
    flex-wrap: wrap;
}

.remote-days-title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
}

.remote-days-subtitle {
    margin: 3px 0 0;
    font-size: 0.77rem;
    color: #64748b;
}

.remote-days-nav {
    display: flex;
    align-items: center;
    gap: 8px;
}

.remote-month-input {
    min-width: 9.2rem;
    height: 34px;
    font-size: 0.78rem;
}

.remote-days-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.remote-days-counter {
    font-size: 0.78rem;
    color: #64748b;
    margin-inline-start: auto;
}

.remote-calendar-wrap {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}

.remote-calendar-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    gap: 6px;
}

.remote-calendar-month {
    margin: 0;
    font-size: 0.92rem;
    font-weight: 700;
    color: #0f172a;
}

.remote-calendar-note {
    margin: 0;
    font-size: 0.72rem;
    color: #64748b;
}

.remote-calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 6px;
    margin-bottom: 7px;
}

.remote-calendar-weekdays span {
    text-align: center;
    font-size: 0.72rem;
    font-weight: 700;
    color: #475569;
    padding: 3px 0;
}

.remote-calendar-weekdays .weekday-friday {
    color: #94a3b8;
}

.remote-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 6px;
}

.remote-calendar-cell-empty {
    min-height: 42px;
}

.remote-calendar-day {
    min-height: 42px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    background: #ffffff;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.84rem;
    font-weight: 700;
    cursor: pointer;
    transition: 0.16s ease;
}

.remote-calendar-day:hover {
    border-color: #94a3b8;
    background: #f8fafc;
}

.remote-calendar-day.is-today {
    box-shadow: inset 0 0 0 1px #334155;
}

.remote-calendar-day.is-selected {
    background: #dbeafe;
    border-color: #60a5fa;
    color: #1e3a8a;
}

.remote-calendar-day.is-disabled {
    background: #f1f5f9;
    color: #94a3b8;
    border-color: #e2e8f0;
    cursor: not-allowed;
}

.remote-calendar-legend {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.7rem;
    color: #64748b;
}

.remote-calendar-legend .dot {
    width: 9px;
    height: 9px;
    border-radius: 999px;
    display: inline-block;
    margin-inline-end: 5px;
    vertical-align: middle;
}

.remote-calendar-legend .dot-selected {
    background: #60a5fa;
}

.remote-calendar-legend .dot-today {
    border: 1px solid #334155;
    background: #ffffff;
}

.remote-calendar-legend .dot-disabled {
    background: #cbd5e1;
}

.remote-selected-wrap {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #f8fafc;
    padding: 10px;
}

.remote-selected-label {
    margin: 0 0 8px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
}

.remote-selected-chips {
    min-height: 26px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.remote-chip {
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    border-radius: 999px;
    padding: 4px 9px;
    font-size: 0.72rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
}

.remote-chip:hover {
    background: #f1f5f9;
}

.remote-selected-helper {
    margin: 8px 0 0;
    font-size: 0.72rem;
    color: #64748b;
}

.remote-selected-helper.is-limit {
    color: #dc2626;
}

@media (max-width: 640px) {
    .remote-days-shell {
        padding: 12px;
    }

    .remote-calendar-grid,
    .remote-calendar-weekdays {
        gap: 4px;
    }

    .remote-calendar-day,
    .remote-calendar-cell-empty {
        min-height: 38px;
    }

    .remote-days-counter {
        width: 100%;
        margin-inline-start: 0;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    function pad(value) {
        return String(value).padStart(2, '0');
    }

    function toIso(year, month, day) {
        return year + '-' + pad(month) + '-' + pad(day);
    }

    function parseMonth(value) {
        if (!/^\d{4}-\d{2}$/.test(value || '')) {
            return null;
        }

        var parts = value.split('-');
        var year = Number(parts[0]);
        var month = Number(parts[1]);

        if (!Number.isFinite(year) || !Number.isFinite(month) || month < 1 || month > 12) {
            return null;
        }

        return { year: year, month: month };
    }

    function getWeekday(isoDate) {
        var parts = isoDate.split('-');
        return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2])).getDay();
    }

    function getMonthLabel(year, month) {
        var d = new Date(year, month - 1, 1);
        return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

    function nowMonth() {
        var now = new Date();
        return now.getFullYear() + '-' + pad(now.getMonth() + 1);
    }

    function renderRemoteDaysSelector(root) {
        var monthPicker = root.querySelector('#remote-month-picker');
        var prevMonthBtn = root.querySelector('[data-remote-month-prev]');
        var nextMonthBtn = root.querySelector('[data-remote-month-next]');
        var clearMonthBtn = root.querySelector('[data-remote-month-clear]');
        var selectWorkdaysBtn = root.querySelector('[data-remote-month-select-workdays]');
        var clearAllBtn = root.querySelector('[data-remote-clear-all]');

        var counterEl = root.querySelector('[data-remote-days-counter]');
        var helperEl = root.querySelector('[data-remote-days-helper]');
        var gridTitle = root.querySelector('[data-remote-days-grid-title]');
        var grid = root.querySelector('[data-remote-days-grid]');
        var selectedContainer = root.querySelector('[data-remote-days-selected]');
        var hiddenInputsContainer = root.querySelector('[data-remote-days-hidden-inputs]');
        var remoteToggle = document.getElementById('is_remote_worker');
        var remoteDaysSection = document.getElementById('remote-days-section');
        var maxDays = Number(root.getAttribute('data-max-days') || 62);

        if (!monthPicker || !grid || !selectedContainer || !hiddenInputsContainer) {
            return;
        }

        var initialDates = [];
        try {
            initialDates = JSON.parse(root.getAttribute('data-initial-dates') || '[]');
        } catch (e) {
            initialDates = [];
        }

        var now = new Date();
        var todayIso = toIso(now.getFullYear(), now.getMonth() + 1, now.getDate());
        var selectedDates = new Set(initialDates.filter(function (iso) {
            return /^\d{4}-\d{2}-\d{2}$/.test(iso) && getWeekday(iso) !== 5 && iso >= todayIso;
        }));
        var activeMonth = (initialDates[0] && initialDates[0].slice(0, 7)) || nowMonth();
        monthPicker.value = activeMonth;

        function updateCounterAndHelper() {
            var total = selectedDates.size;
            var inMonth = 0;

            Array.from(selectedDates).forEach(function (dateStr) {
                if (dateStr.slice(0, 7) === activeMonth) {
                    inMonth += 1;
                }
            });

            counterEl.textContent = 'المجموع: ' + total + ' يوم | داخل الشهر: ' + inMonth;
            helperEl.classList.toggle('is-limit', total >= maxDays);
            helperEl.textContent = total >= maxDays
                ? 'وصلت للحد الأقصى (' + maxDays + ' يوم). احذف بعض الأيام لاختيار غيرها.'
                : 'تعطيل الأيام الماضية مفعّل تلقائيًا دائمًا.';
        }

        function renderHiddenInputs() {
            hiddenInputsContainer.innerHTML = '';
            Array.from(selectedDates).sort().forEach(function (date) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'remote_allowed_dates[]';
                input.value = date;
                hiddenInputsContainer.appendChild(input);
            });
        }

        function renderSelectedChips() {
            selectedContainer.innerHTML = '';
            var dates = Array.from(selectedDates).sort();

            if (dates.length === 0) {
                var empty = document.createElement('span');
                empty.className = 'text-xs text-slate-400';
                empty.textContent = 'لا توجد أيام مختارة.';
                selectedContainer.appendChild(empty);
                renderHiddenInputs();
                updateCounterAndHelper();
                return;
            }

            dates.forEach(function (iso) {
                var chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'remote-chip';
                chip.textContent = iso;

                var x = document.createElement('span');
                x.textContent = '×';
                x.className = 'text-slate-400';
                chip.appendChild(x);

                chip.addEventListener('click', function () {
                    selectedDates.delete(iso);
                    renderSelectedChips();
                    renderCalendar();
                });

                selectedContainer.appendChild(chip);
            });

            renderHiddenInputs();
            updateCounterAndHelper();
        }

        function trySelect(iso) {
            if (selectedDates.has(iso)) {
                return true;
            }

            if (selectedDates.size >= maxDays) {
                window.alert('لا يمكن اختيار أكثر من ' + maxDays + ' يوم.');
                return false;
            }

            selectedDates.add(iso);
            return true;
        }

        function renderCalendar() {
            var monthInfo = parseMonth(activeMonth);
            if (!monthInfo) {
                return;
            }

            var year = monthInfo.year;
            var month = monthInfo.month;
            var daysCount = new Date(year, month, 0).getDate();
            var firstWeekday = new Date(year, month - 1, 1).getDay();

            gridTitle.textContent = getMonthLabel(year, month);
            grid.innerHTML = '';

            var cells = [];
            for (var i = 0; i < firstWeekday; i += 1) {
                cells.push({ empty: true });
            }
            for (var day = 1; day <= daysCount; day += 1) {
                cells.push({ empty: false, day: day, iso: toIso(year, month, day) });
            }
            while (cells.length % 7 !== 0) {
                cells.push({ empty: true });
            }

            cells.forEach(function (cell) {
                if (cell.empty) {
                    var blank = document.createElement('div');
                    blank.className = 'remote-calendar-cell-empty';
                    grid.appendChild(blank);
                    return;
                }

                var iso = cell.iso;
                var weekday = getWeekday(iso);
                var isFriday = weekday === 5;
                var isPast = iso < todayIso;
                var isDisabled = isFriday || isPast;
                var isToday = iso === todayIso;
                var isSelected = selectedDates.has(iso);

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'remote-calendar-day';
                btn.textContent = String(cell.day);
                btn.title = iso;

                if (isToday) {
                    btn.classList.add('is-today');
                }
                if (isSelected) {
                    btn.classList.add('is-selected');
                }
                if (isDisabled) {
                    btn.classList.add('is-disabled');
                    btn.disabled = true;
                }

                btn.addEventListener('click', function () {
                    if (isDisabled) {
                        return;
                    }

                    if (selectedDates.has(iso)) {
                        selectedDates.delete(iso);
                    } else if (!trySelect(iso)) {
                        return;
                    }

                    renderSelectedChips();
                    renderCalendar();
                });

                grid.appendChild(btn);
            });
        }

        function shiftMonth(step) {
            var info = parseMonth(activeMonth);
            if (!info) {
                return;
            }

            var shifted = new Date(info.year, info.month - 1 + step, 1);
            activeMonth = shifted.getFullYear() + '-' + pad(shifted.getMonth() + 1);
            monthPicker.value = activeMonth;
            renderCalendar();
            updateCounterAndHelper();
        }

        function syncSectionVisibility() {
            if (!remoteToggle || !remoteDaysSection) {
                return;
            }

            remoteDaysSection.classList.toggle('hidden', !remoteToggle.checked);

            if (!remoteToggle.checked && selectedDates.size > 0) {
                selectedDates.clear();
                renderSelectedChips();
                renderCalendar();
            }
        }

        if (prevMonthBtn) {
            prevMonthBtn.addEventListener('click', function () { shiftMonth(-1); });
        }

        if (nextMonthBtn) {
            nextMonthBtn.addEventListener('click', function () { shiftMonth(1); });
        }

        if (monthPicker) {
            monthPicker.addEventListener('change', function () {
                var parsed = parseMonth(monthPicker.value);
                if (!parsed) {
                    monthPicker.value = activeMonth;
                    return;
                }

                activeMonth = monthPicker.value;
                renderCalendar();
                updateCounterAndHelper();
            });
        }

        if (clearMonthBtn) {
            clearMonthBtn.addEventListener('click', function () {
                Array.from(selectedDates).forEach(function (iso) {
                    if (iso.slice(0, 7) === activeMonth) {
                        selectedDates.delete(iso);
                    }
                });
                renderSelectedChips();
                renderCalendar();
            });
        }

        if (selectWorkdaysBtn) {
            selectWorkdaysBtn.addEventListener('click', function () {
                var info = parseMonth(activeMonth);
                if (!info) {
                    return;
                }

                var daysCount = new Date(info.year, info.month, 0).getDate();
                for (var d = 1; d <= daysCount; d += 1) {
                    var iso = toIso(info.year, info.month, d);
                    if (iso < todayIso || getWeekday(iso) === 5) {
                        continue;
                    }
                    if (!trySelect(iso)) {
                        break;
                    }
                }

                renderSelectedChips();
                renderCalendar();
            });
        }

        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function () {
                selectedDates.clear();
                renderSelectedChips();
                renderCalendar();
            });
        }

        if (remoteToggle) {
            remoteToggle.addEventListener('change', syncSectionVisibility);
        }

        renderSelectedChips();
        renderCalendar();
        syncSectionVisibility();
    }

    document.querySelectorAll('[data-remote-days-root]').forEach(renderRemoteDaysSelector);
})();
</script>
@endpush
