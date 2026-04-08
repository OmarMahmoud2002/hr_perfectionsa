<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobTitleRequest;
use App\Http\Requests\UpdateJobTitleRequest;
use App\Models\Employee;
use App\Models\JobTitle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JobTitleController extends Controller
{
    public function index(): View
    {
        $jobTitles = JobTitle::query()
            ->withCount('employees')
            ->orderByDesc('is_system')
            ->orderBy('name_ar')
            ->get();

        return view('job-titles.index', compact('jobTitles'));
    }

    public function create(): View
    {
        $employees = Employee::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'job_title_id']);

        return view('job-titles.create', compact('employees'));
    }

    public function store(StoreJobTitleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $generatedKey = $this->generateUniqueKeyFromName((string) $validated['name_ar']);

        $jobTitle = JobTitle::query()->create([
            'key' => $generatedKey,
            'name_ar' => $validated['name_ar'],
            'system_role_mapping' => $validated['system_role_mapping'] ?? null,
            'is_system' => false,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_by_user_id' => (int) $request->user()->id,
        ]);

        $this->syncEmployees($jobTitle, $request->input('employee_ids', []), true);

        if ((string) $request->input('submit_action') === 'save_and_add_new') {
            return redirect()
                ->route('job-titles.create')
                ->with('success', 'تمت إضافة الوظيفة بنجاح. يمكنك إضافة وظيفة أخرى الآن.');
        }

        return redirect()
            ->route('job-titles.index')
            ->with('success', 'تمت إضافة الوظيفة بنجاح.');
    }

    public function edit(JobTitle $jobTitle): View
    {
        $jobTitle->loadMissing('employees:id,name,job_title_id');

        $employees = Employee::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'job_title_id']);

        return view('job-titles.edit', compact('jobTitle', 'employees'));
    }

    public function update(UpdateJobTitleRequest $request, JobTitle $jobTitle): RedirectResponse
    {
        $validated = $request->validated();

        if ($jobTitle->is_system) {
            $jobTitle->update([
                'name_ar' => $validated['name_ar'],
                'system_role_mapping' => $validated['system_role_mapping'] ?? $jobTitle->system_role_mapping,
                'is_active' => (bool) ($validated['is_active'] ?? $jobTitle->is_active),
            ]);
        } else {
            $updateData = [
                'name_ar' => $validated['name_ar'],
                'system_role_mapping' => $validated['system_role_mapping'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? $jobTitle->is_active),
            ];

            if (array_key_exists('key', $validated) && ! empty($validated['key'])) {
                $updateData['key'] = $validated['key'];
            }

            $jobTitle->update($updateData);
        }

        $this->syncEmployees(
            $jobTitle,
            $request->input('employee_ids', []),
            $request->boolean('manage_employee_assignments')
        );

        return redirect()
            ->route('job-titles.index')
            ->with('success', 'تم تحديث الوظيفة بنجاح.');
    }

    public function toggle(JobTitle $jobTitle, Request $request): RedirectResponse
    {
        if ($jobTitle->is_system) {
            return back()->with('error', 'لا يمكن تعطيل الوظائف النظامية.');
        }

        $jobTitle->update(['is_active' => ! $jobTitle->is_active]);

        return back()->with('success', 'تم تحديث حالة الوظيفة بنجاح.');
    }

    public function destroy(JobTitle $jobTitle): RedirectResponse
    {
        if ($jobTitle->is_system) {
            return back()->with('error', 'لا يمكن حذف وظيفة نظامية.');
        }

        DB::transaction(function () use ($jobTitle): void {
            Employee::query()
                ->where('job_title_id', (int) $jobTitle->id)
                ->update([
                    'job_title_id' => null,
                    'job_title' => null,
                ]);

            $jobTitle->delete();
        });

        return redirect()
            ->route('job-titles.index')
            ->with('success', 'تم حذف الوظيفة بنجاح.');
    }

    private function syncEmployees(JobTitle $jobTitle, mixed $employeeIds, bool $shouldSync): void
    {
        if (! $shouldSync) {
            return;
        }

        $ids = collect(is_array($employeeIds) ? $employeeIds : [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($jobTitle, $ids): void {
            Employee::query()
                ->where('job_title_id', (int) $jobTitle->id)
                ->whereNotIn('id', $ids)
                ->update([
                    'job_title_id' => null,
                    'job_title' => null,
                ]);

            if (! empty($ids)) {
                Employee::query()
                    ->whereIn('id', $ids)
                    ->update([
                        'job_title_id' => (int) $jobTitle->id,
                        'job_title' => $jobTitle->key,
                    ]);
            }
        });
    }

    private function generateUniqueKeyFromName(string $name): string
    {
        $base = Str::slug($name, '_');
        if ($base === '') {
            $base = 'job_title';
        }

        $key = $base;
        $counter = 2;

        while (JobTitle::query()->where('key', $key)->exists()) {
            $key = $base . '_' . $counter;
            $counter++;
        }

        return $key;
    }
}
