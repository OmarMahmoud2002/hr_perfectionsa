@php
    $formId = $formId ?? '';
    $mode = $mode ?? 'department';
@endphp

@push('scripts')
<script>
(function () {
    var form = document.getElementById('{{ $formId }}');
    if (!form || form.dataset.reassignHandlerBound === '1') {
        return;
    }

    form.dataset.reassignHandlerBound = '1';

    function confirmWithModal(message, onConfirm) {
        if (typeof window.showConfirm === 'function') {
            window.showConfirm({
                title: 'تأكيد نقل الموظفين',
                message: message,
                confirmText: 'نعم، نفذ النقل',
                cancelText: 'إلغاء',
                type: 'warning',
                onConfirm: onConfirm,
            });

            return;
        }

        if (window.confirm(message)) {
            onConfirm();
        }
    }

    function resetLoadingState() {
        form.dataset.loadingSubmitted = '0';
        var flag = form.querySelector('[data-confirm-reassignment-flag]');
        if (flag && flag.value !== '1') {
            flag.value = '0';
        }

        if (typeof window.hideGlobalLoading === 'function') {
            window.hideGlobalLoading();
        }
    }

    function collectDepartmentConflicts() {
        var conflicts = [];
        var tracked = new Set();

        var managerSelect = form.querySelector('select[name="manager_employee_id"]');
        if (managerSelect && managerSelect.value !== '') {
            var managerOption = managerSelect.options[managerSelect.selectedIndex];
            if (managerOption && managerOption.dataset.currentDepartmentId) {
                tracked.add(managerOption.value);
                conflicts.push(managerOption.text.trim() + ' (' + (managerOption.dataset.currentDepartmentName || 'قسم غير محدد') + ')');
            }
        }

        var memberChecks = form.querySelectorAll('input[name="employee_ids[]"]:checked');
        if (!memberChecks.length) {
            return conflicts;
        }

        Array.from(memberChecks).forEach(function (input) {
            if (!input.dataset.currentDepartmentId || tracked.has(input.value)) {
                return;
            }

            tracked.add(input.value);
            conflicts.push((input.dataset.employeeName || 'موظف') + ' (' + (input.dataset.currentDepartmentName || 'قسم غير محدد') + ')');
        });

        return conflicts;
    }

    function collectJobTitleConflicts() {
        var memberChecks = form.querySelectorAll('input[name="employee_ids[]"]:checked');
        if (!memberChecks.length) {
            return [];
        }

        return Array.from(memberChecks)
            .filter(function (input) {
                return !!input.dataset.currentJobTitleId;
            })
            .map(function (input) {
                return (input.dataset.employeeName || 'موظف') + ' (' + (input.dataset.currentJobTitleName || 'وظيفة غير محددة') + ')';
            });
    }

    form.addEventListener('submit', function (event) {
        var flag = form.querySelector('[data-confirm-reassignment-flag]');
        if (!flag || flag.value === '1') {
            return;
        }

        var mode = '{{ $mode }}';
        var conflicts = mode === 'job_title' ? collectJobTitleConflicts() : collectDepartmentConflicts();

        if (conflicts.length === 0) {
            return;
        }

        event.preventDefault();
        resetLoadingState();

        var preview = conflicts.slice(0, 8).join('، ');
        var suffix = conflicts.length > 8 ? ' ...' : '';
        var messagePrefix = mode === 'job_title'
            ? 'هذا الحفظ سينقل موظفين من وظائفهم الحالية: '
            : 'هذا الحفظ سينقل موظفين من أقسامهم الحالية: ';

        confirmWithModal(messagePrefix + preview + suffix + '. هل تريد المتابعة؟', function () {
            flag.value = '1';
            form.submit();
        });
    });
})();
</script>
@endpush
