# Hybrid Attendance (Office + Remote GPS) - Simple & Maintainable Plan

## 1) Core Direction (No Over-Engineering)

This plan intentionally keeps implementation minimal:
- No extra abstraction layers like `AttendanceSourceResolver`.
- No extra audit tables like `attendance_geo_events`.
- Keep one source of truth: `attendance_records`.
- Keep payroll flow unchanged as much as possible.
- Add only required fields and simple validation rules.

## 2) What Changes, Exactly

### 2.1 Keep existing attendance flow
- Excel import remains the office source.
- Remote check-in/out writes to the same `attendance_records` table.
- Payroll continues reading `attendance_records` through existing services.

### 2.2 Add multiple allowed locations per employee
- New `locations` table.
- New pivot `employee_location`.
- Each employee can have many allowed locations.
- Attendance is valid if user is inside at least one assigned location.

## 3) Database Changes

## 3.1 Migration: create `locations` table
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('locations', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->decimal('latitude', 10, 7);
      $table->decimal('longitude', 10, 7);
      $table->unsignedInteger('radius'); // meters
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('locations');
  }
};
```

### 3.2 Migration: create `employee_location` pivot table
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('employee_location', function (Blueprint $table) {
      $table->id();
      $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
      $table->foreignId('location_id')->constrained()->cascadeOnDelete();
      $table->timestamps();

      $table->unique(['employee_id', 'location_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('employee_location');
  }
};
```

### 3.3 Migration: update `attendance_records` (minimal fields only)
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('attendance_records', function (Blueprint $table) {
      $table->foreignId('import_batch_id')->nullable()->change();

      $table->enum('source', ['excel', 'system'])->default('excel')->after('import_batch_id');
      $table->enum('type', ['office', 'remote'])->default('office')->after('source');

      $table->decimal('latitude', 10, 7)->nullable()->after('type');
      $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

      $table->string('ip_address', 45)->nullable()->after('longitude');
      $table->text('device_info')->nullable()->after('ip_address');
      $table->string('photo_path', 500)->nullable()->after('device_info');
    });
  }

  public function down(): void
  {
    Schema::table('attendance_records', function (Blueprint $table) {
      $table->dropColumn([
        'source', 'type', 'latitude', 'longitude', 'ip_address', 'device_info', 'photo_path'
      ]);
    });
  }
};
```

Note:
- If your database driver needs `doctrine/dbal` for `change()`, install it before running migration.

## 4) Model Updates (simple)

### 4.1 `Location` model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Location extends Model
{
  protected $fillable = ['name', 'latitude', 'longitude', 'radius'];

  public function employees(): BelongsToMany
  {
    return $this->belongsToMany(Employee::class, 'employee_location');
  }
}
```

### 4.2 Employee relation
Add to `Employee`:
```php
public function locations(): BelongsToMany
{
  return $this->belongsToMany(Location::class, 'employee_location');
}
```

### 4.3 AttendanceRecord fillable
Add to `AttendanceRecord::$fillable`:
- `source`, `type`, `latitude`, `longitude`, `ip_address`, `device_info`, `photo_path`

## 5) Remote Attendance API (Simple)

### 5.1 Routes
Use web auth (simple for current Blade app):
```php
Route::middleware(['auth'])->group(function () {
  Route::post('/attendance/check-in', [RemoteAttendanceController::class, 'checkIn']);
  Route::post('/attendance/check-out', [RemoteAttendanceController::class, 'checkOut']);
});
```

### 5.2 Controller (single simple geofence loop)
```php
<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RemoteAttendanceController extends Controller
{
  public function checkIn(Request $request)
  {
    $data = $request->validate([
      'latitude' => ['required', 'numeric', 'between:-90,90'],
      'longitude' => ['required', 'numeric', 'between:-180,180'],
      'accuracy' => ['nullable', 'numeric', 'min:0', 'max:300'],
      'photo' => ['nullable', 'image', 'max:4096'],
    ]);

    $user = $request->user();
    $employee = $user->employee;

    if (!$employee) {
      return response()->json(['message' => 'Employee not found'], 404);
    }

    $locations = $employee->locations()->get();
    if ($locations->isEmpty()) {
      return response()->json(['message' => 'No allowed locations assigned'], 422);
    }

    if (!$this->insideAnyLocation((float) $data['latitude'], (float) $data['longitude'], $locations)) {
      return response()->json(['message' => 'You are خارج نطاق العمل'], 422);
    }

    $today = Carbon::today()->toDateString();
    $existing = AttendanceRecord::where('employee_id', $employee->id)->where('date', $today)->first();

    if ($existing && $existing->clock_in) {
      return response()->json(['message' => 'Already checked in'], 409);
    }

    $photoPath = $request->hasFile('photo') ? $request->file('photo')->store('attendance-photos', 'public') : null;

    AttendanceRecord::updateOrCreate(
      ['employee_id' => $employee->id, 'date' => $today],
      [
        'clock_in' => now()->format('H:i:s'),
        'is_absent' => false,
        'source' => 'system',
        'type' => 'remote',
        'latitude' => $data['latitude'],
        'longitude' => $data['longitude'],
        'ip_address' => $request->ip(),
        'device_info' => (string) $request->userAgent(),
        'photo_path' => $photoPath,
        'import_batch_id' => null,
      ]
    );

    return response()->json(['message' => 'Check-in recorded']);
  }

  public function checkOut(Request $request)
  {
    $data = $request->validate([
      'latitude' => ['required', 'numeric', 'between:-90,90'],
      'longitude' => ['required', 'numeric', 'between:-180,180'],
      'accuracy' => ['nullable', 'numeric', 'min:0', 'max:300'],
    ]);

    $employee = $request->user()->employee;
    if (!$employee) {
      return response()->json(['message' => 'Employee not found'], 404);
    }

    $locations = $employee->locations()->get();
    if ($locations->isEmpty()) {
      return response()->json(['message' => 'No allowed locations assigned'], 422);
    }

    if (!$this->insideAnyLocation((float) $data['latitude'], (float) $data['longitude'], $locations)) {
      return response()->json(['message' => 'You are خارج نطاق العمل'], 422);
    }

    $today = Carbon::today()->toDateString();
    $record = AttendanceRecord::where('employee_id', $employee->id)->where('date', $today)->first();

    if (!$record || !$record->clock_in) {
      return response()->json(['message' => 'Cannot check-out before check-in'], 409);
    }

    if ($record->clock_out) {
      return response()->json(['message' => 'Already checked out'], 409);
    }

    $record->update([
      'clock_out' => now()->format('H:i:s'),
      'source' => 'system',
      'type' => 'remote',
      'latitude' => $data['latitude'],
      'longitude' => $data['longitude'],
      'ip_address' => $request->ip(),
      'device_info' => (string) $request->userAgent(),
    ]);

    return response()->json(['message' => 'Check-out recorded']);
  }

  private function insideAnyLocation(float $lat, float $lng, $locations): bool
  {
    foreach ($locations as $location) {
      $distance = $this->haversineMeters($lat, $lng, (float) $location->latitude, (float) $location->longitude);
      if ($distance <= (float) $location->radius) {
        return true;
      }
    }

    return false;
  }

  private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
  {
    $earth = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) ** 2
      + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

    return 2 * $earth * asin(min(1, sqrt($a)));
  }
}
```

## 6) Keep Payroll Safe

Minimal required backend adjustment:
- In import processing, do not delete system/remote records.
- When upserting Excel rows, set:
  - `source = excel`
  - `type = office`
- Excel keeps priority naturally on same day because import runs as final upsert for office records.

No new payroll calculation path is needed.

## 7) Blade Admin UI (Simple + Professional)

### 7.0 Google Maps API key integration
Use the API key from ENV in the map pages:
```html
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initLocationPickerMap" async defer></script>
```

This is used for:
- map display
- location picker (click to set lat/lng)
- radius circle drawing and live updates

## 7.1 Location form (map picker + live radius circle)
Example Blade section:
```blade
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block mb-1">Location Name</label>
    <input type="text" name="name" class="w-full border rounded px-3 py-2" required>
  </div>

  <div>
    <label class="block mb-1">Radius (meters)</label>
    <input id="radiusInput" type="number" name="radius" min="10" value="100" class="w-full border rounded px-3 py-2" required>
  </div>

  <div>
    <label class="block mb-1">Latitude</label>
    <input id="latitudeInput" type="text" name="latitude" class="w-full border rounded px-3 py-2" required>
  </div>

  <div>
    <label class="block mb-1">Longitude</label>
    <input id="longitudeInput" type="text" name="longitude" class="w-full border rounded px-3 py-2" required>
  </div>
</div>

<div id="map" style="height: 420px;" class="mt-4 rounded border"></div>
```

Behavior:
- admin clicks the map -> latitude/longitude fields are updated
- circle center follows selected point
- circle radius equals admin input (meters)
- when radius input changes, circle radius updates instantly

## 7.1.1 Reuse saved locations (dropdown)
```blade
<div class="mt-4">
  <label class="block mb-1">Saved Locations</label>
  <select id="savedLocationSelect" class="w-full border rounded px-3 py-2">
    <option value="">Select saved location</option>
    @foreach($locations as $loc)
      <option
        value="{{ $loc->id }}"
        data-lat="{{ $loc->latitude }}"
        data-lng="{{ $loc->longitude }}"
        data-radius="{{ $loc->radius }}"
      >
        {{ $loc->name }} ({{ $loc->radius }}m)
      </option>
    @endforeach
  </select>
</div>
```

## 7.2 Employee form: assign multiple locations
```blade
<label class="block mb-1">Allowed Locations</label>
<select name="location_ids[]" id="locationSelect" multiple class="w-full border rounded px-3 py-2">
  @foreach($locations as $location)
    <option value="{{ $location->id }}" @selected(in_array($location->id, old('location_ids', $selectedLocationIds ?? [])))>
      {{ $location->name }} ({{ $location->radius }}m)
    </option>
  @endforeach
</select>

<div id="employeeMap" style="height: 420px;" class="mt-4 rounded border"></div>
```

## 7.3 Google Maps JS (single map + multiple circles)
```html
<script>
let map;
let marker;
let radiusCircle;
let locationCircles = [];

function initLocationPickerMap() {
  const cairo = { lat: 30.0444, lng: 31.2357 };
  map = new google.maps.Map(document.getElementById('map'), {
    center: cairo,
    zoom: 12,
  });

  marker = new google.maps.Marker({ map, position: cairo, draggable: true });

  radiusCircle = new google.maps.Circle({
    map,
    center: cairo,
    radius: Number(document.getElementById('radiusInput').value || 100),
    fillColor: '#2563eb',
    fillOpacity: 0.2,
    strokeColor: '#1d4ed8',
    strokeWeight: 2,
  });

  const latInput = document.getElementById('latitudeInput');
  const lngInput = document.getElementById('longitudeInput');
  const radiusInput = document.getElementById('radiusInput');
  const savedLocationSelect = document.getElementById('savedLocationSelect');

  function setLatLng(latLng) {
    latInput.value = latLng.lat().toFixed(7);
    lngInput.value = latLng.lng().toFixed(7);
    marker.setPosition(latLng);
    radiusCircle.setCenter(latLng);
  }

  map.addListener('click', (e) => setLatLng(e.latLng));
  marker.addListener('dragend', (e) => setLatLng(e.latLng));

  radiusInput.addEventListener('input', () => {
    radiusCircle.setRadius(Number(radiusInput.value || 0));
  });

  if (savedLocationSelect) {
    savedLocationSelect.addEventListener('change', function () {
      const option = this.options[this.selectedIndex];
      if (!option || !option.dataset.lat || !option.dataset.lng) return;

      const lat = Number(option.dataset.lat);
      const lng = Number(option.dataset.lng);
      const radius = Number(option.dataset.radius || 100);

      const latLng = new google.maps.LatLng(lat, lng);
      setLatLng(latLng);
      radiusInput.value = radius;
      radiusCircle.setRadius(radius);
      map.panTo(latLng);
      map.setZoom(15);
    });
  }
}

function renderEmployeeLocationCircles(locations) {
  const mapEl = document.getElementById('employeeMap');
  if (!mapEl) return;

  const cairo = { lat: 30.0444, lng: 31.2357 };
  const employeeMap = new google.maps.Map(mapEl, { center: cairo, zoom: 11 });

  locations.forEach((loc) => {
    const center = { lat: Number(loc.latitude), lng: Number(loc.longitude) };
    new google.maps.Marker({ map: employeeMap, position: center, title: loc.name });

    const circle = new google.maps.Circle({
      map: employeeMap,
      center,
      radius: Number(loc.radius),
      fillColor: '#16a34a',
      fillOpacity: 0.15,
      strokeColor: '#15803d',
      strokeWeight: 2,
    });

    locationCircles.push(circle);
  });
}
</script>
```

## 8) Minimal Controller Updates for Employee Assignment

When creating/updating employee:
```php
$employee = $this->employeeService->create($request->validated());
$employee->locations()->sync($request->input('location_ids', []));
```

Do the same in update flow.

## 9) Validation Rules (Keep It Basic)

- GPS required: `latitude`, `longitude`.
- Accuracy check: optional, simple max value only.
- Geofence: distance must be within at least one assigned location radius.
- Radius rule is explicit and simple:
  - if `distance <= radius` -> allow
  - if `distance > radius` -> reject
- No complex priority/rule engine.

## 10) Final Simple Implementation Order

1. Add migrations for `locations`, `employee_location`, and minimal `attendance_records` fields.
2. Add `Location` model + relations in `Employee`.
3. Add location CRUD (Blade + map picker + live radius circle).
4. Add employee multi-location assignment UI (`location_ids[]`).
5. Add `RemoteAttendanceController` check-in/check-out with simple geofence loop.
6. Update import to tag office rows with `source=excel`, `type=office` and avoid deleting remote rows.
7. Run regression check for attendance report + payroll calculation.

This approach delivers hybrid attendance with multi-location support while keeping code small, readable, and easy to maintain.
