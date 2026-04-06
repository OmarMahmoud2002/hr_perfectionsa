<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        $locations = Location::query()
            ->latest()
            ->paginate(15);

        return view('locations.index', compact('locations'));
    }

    public function create(): View
    {
        $locations = Location::query()
            ->orderBy('name')
            ->get(['id', 'name', 'latitude', 'longitude', 'radius']);

        return view('locations.create', compact('locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:10', 'max:100000'],
            'google_maps_url' => ['nullable', 'url', 'max:1000'],
        ]);

        $location = Location::create($data);

        return redirect()
            ->route('locations.index')
            ->with('success', "تم حفظ الموقع «{$location->name}» بنجاح.");
    }

    public function edit(Location $location): View
    {
        $locations = Location::query()
            ->where('id', '!=', $location->id)
            ->orderBy('name')
            ->get(['id', 'name', 'latitude', 'longitude', 'radius']);

        return view('locations.edit', compact('location', 'locations'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:10', 'max:100000'],
            'google_maps_url' => ['nullable', 'url', 'max:1000'],
        ]);

        $location->update($data);

        return redirect()
            ->route('locations.index')
            ->with('success', "تم تحديث الموقع «{$location->name}» بنجاح.");
    }

    public function destroy(Location $location): RedirectResponse
    {
        $name = $location->name;
        $location->delete();

        return redirect()
            ->route('locations.index')
            ->with('success', "تم حذف الموقع «{$name}» بنجاح.");
    }
}
