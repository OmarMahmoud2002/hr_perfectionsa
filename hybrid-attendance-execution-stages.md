# Hybrid Attendance Execution Stages (Action Playbook)

This file is the execution reference for implementing the simplified hybrid attendance system.
When you say: "Execute Stage X", I will perform exactly that stage.

## Execution Rules

- Keep implementation simple and readable.
- No over-engineering.
- Keep one attendance source of truth: `attendance_records`.
- Do not break existing payroll logic.
- Complete each stage fully before moving to the next.

---

## Stage 0 - Pre-Execution Safety Check

## Goal
Confirm current project state and avoid risky changes.

## Steps
1. Check git status and current changed files.
2. Verify existing migration state and key attendance/payroll files.
3. Confirm no destructive operations will be used.

## Done Criteria
- Current workspace state is known.
- No unrelated files are modified.

---

## Stage 1 - Database Foundations

## Goal
Create location infrastructure and minimal attendance metadata.

## Steps
1. Create migration: `locations` table with:
   - `id`
   - `name`
   - `latitude`
   - `longitude`
   - `radius` (meters)
   - timestamps
2. Create migration: `employee_location` pivot with:
   - `employee_id`
   - `location_id`
   - unique composite index (`employee_id`, `location_id`)
3. Create migration: update `attendance_records` with minimal fields:
   - make `import_batch_id` nullable
   - `source` (`excel`, `system`)
   - `type` (`office`, `remote`)
   - `latitude`, `longitude`
   - `ip_address`, `device_info`, `photo_path`
4. Keep down methods clean and reversible.

## Done Criteria
- 3 migrations created and syntactically valid.
- Schema aligns with simplified plan.

---

## Stage 2 - Models and Relationships

## Goal
Enable location assignment and attendance metadata mapping.

## Steps
1. Add `Location` model.
2. Add relation in `Location`:
   - `employees()` many-to-many via `employee_location`.
3. Add relation in `Employee`:
   - `locations()` many-to-many via `employee_location`.
4. Update `AttendanceRecord` fillable/casts for new columns.

## Done Criteria
- Eloquent relations work for assigning locations to employees.
- `AttendanceRecord` accepts new fields safely.

---

## Stage 3 - Admin Location CRUD + Map Picker UI

## Goal
Allow admin to create/edit reusable locations with map and radius circle.

## Steps
1. Add `LocationController` for basic CRUD.
2. Add routes (admin/manager/hr scope) for locations.
3. Add Blade views for:
   - list locations
   - create/edit location form
4. Integrate Google Maps API in location form using key:
   - from ENV: `GOOGLE_MAPS_API_KEY`
5. Implement map picker behavior:
   - click map to set latitude/longitude
   - draw circle
   - circle radius tied to radius input
   - live circle update on radius change
6. Add saved location dropdown reuse behavior where needed.

## Done Criteria
- Admin can save location with name/lat/lng/radius.
- Circle updates instantly when radius changes.

---

## Stage 4 - Employee Multi-Location Assignment UI

## Goal
Assign multiple allowed locations to each employee.

## Steps
1. Update employee create/edit forms:
   - multi-select `location_ids[]` or checkboxes.
2. Load all locations for form rendering.
3. On save/update, sync pivot:
   - `$employee->locations()->sync($request->input('location_ids', []));`
4. Optionally render selected location circles on employee map preview.

## Done Criteria
- Employee can have multiple assigned locations.
- Assignments persist correctly in pivot table.

---

## Stage 5 - Remote Attendance Endpoints (Simple)

## Goal
Implement check-in/check-out with simple geofence validation.

## Steps
1. Add routes:
   - `POST /attendance/check-in`
   - `POST /attendance/check-out`
2. Create `RemoteAttendanceController` with:
   - `checkIn`
   - `checkOut`
   - `insideAnyLocation`
   - `haversineMeters`
3. Validation (simple):
   - `latitude`, `longitude` required
   - optional `accuracy` basic max
   - optional `photo`
4. Check-in logic:
   - get authenticated employee
   - get assigned locations
   - loop locations
   - if inside ANY (`distance <= radius`) allow
   - otherwise reject
   - prevent duplicate check-in
   - store GPS + IP + user-agent + optional photo
5. Check-out logic:
   - same location validation
   - require existing same-day check-in
   - prevent duplicate check-out
   - store metadata

## Done Criteria
- Check-in/out works only inside any assigned location.
- Responses are clean and predictable.

---

## Stage 6 - Import and Payroll Safety Adjustments

## Goal
Keep payroll stable while integrating office + remote attendance.

## Steps
1. Update import behavior to avoid deleting remote/system records.
2. Ensure Excel import upsert sets:
   - `source = excel`
   - `type = office`
3. Ensure remote writes set:
   - `source = system`
   - `type = remote`
4. Verify payroll services still consume unified `attendance_records` without formula changes.

## Done Criteria
- Existing payroll logic remains intact.
- Office and remote coexist in one table safely.

---

## Stage 7 - Validation and Regression Checks

## Goal
Verify feature correctness and avoid regressions.

## Steps
1. Run migration and basic app checks.
2. Manually test:
   - create location
   - radius circle behavior
   - assign multiple locations to employee
   - check-in inside radius (allowed)
   - check-in outside all radii (rejected)
   - check-out flow
3. Verify attendance reports and payroll screens still work.

## Done Criteria
- Core scenarios pass.
- No breaking errors in attendance/payroll flows.

---

## Quick Command Interface (How to instruct execution)

Use these exact prompts:
- "Execute Stage 1"
- "Execute Stage 2"
- ...
- "Execute Stage 7"

Optional scoped prompts:
- "Execute Stage 3 only (no styling changes)"
- "Execute Stage 5 and include form requests"
- "Execute Stage 6 and run quick regression checks"

---

## Notes

- If a stage uncovers blockers (schema conflict, missing package, route collision), I will stop and report exactly what changed and what decision is needed.
- I will not execute future stages automatically unless you request them.
