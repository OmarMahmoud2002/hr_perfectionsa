@extends('layouts.app')

@section('title', 'تعديل موقع: ' . $location->name)
@section('page-title', 'تعديل الموقع')
@section('page-subtitle', $location->name)

@section('content')

<nav class="breadcrumb">
    <a href="{{ route('locations.index') }}">المواقع</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    <span class="text-slate-700 font-medium">تعديل الموقع</span>
</nav>

<div class="max-w-4xl mx-auto">
    <div class="card overflow-hidden">

        <div class="px-6 py-5 border-b border-slate-100"
             style="background: linear-gradient(135deg, rgba(231,197,57,0.06), rgba(69,150,207,0.06));">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center"
                     style="background: linear-gradient(135deg, #4596cf, #4d9b97);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.243-4.243a8 8 0 1111.313 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-slate-800">{{ $location->name }}</h2>
                    <p class="text-xs text-slate-500">قم بتعديل الإحداثيات أو نطاق السماح ثم احفظ التغييرات</p>
                </div>
            </div>
        </div>

        <form action="{{ route('locations.update', $location) }}" method="POST" class="p-6 space-y-5"
              data-loading="true" data-loading-target="#edit-location-submit" data-loading-text="جاري الحفظ...">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group mb-0">
                    <label for="name" class="form-label">اسم الموقع <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name"
                           value="{{ old('name', $location->name) }}"
                           class="form-input @error('name') border-red-400 @enderror">
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group mb-0">
                    <label for="radiusInput" class="form-label">نطاق السماح (متر) <span class="text-red-500">*</span></label>
                    <input type="number" id="radiusInput" name="radius"
                           min="10" max="100000"
                           value="{{ old('radius', $location->radius) }}"
                           class="form-input @error('radius') border-red-400 @enderror">
                    @error('radius')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group mb-0">
                    <label for="latitudeInput" class="form-label">دائرة العرض <span class="text-red-500">*</span></label>
                    <input type="text" id="latitudeInput" name="latitude"
                           value="{{ old('latitude', number_format((float) $location->latitude, 7, '.', '')) }}"
                           class="form-input @error('latitude') border-red-400 @enderror">
                    @error('latitude')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group mb-0">
                    <label for="longitudeInput" class="form-label">خط الطول <span class="text-red-500">*</span></label>
                    <input type="text" id="longitudeInput" name="longitude"
                           value="{{ old('longitude', number_format((float) $location->longitude, 7, '.', '')) }}"
                           class="form-input @error('longitude') border-red-400 @enderror">
                    @error('longitude')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group mb-0 md:col-span-2">
                    <label for="google_maps_url" class="form-label">رابط Google Maps (اختياري)</label>
                    <input type="url" id="google_maps_url" name="google_maps_url"
                           value="{{ old('google_maps_url', $location->google_maps_url) }}"
                           placeholder="https://maps.google.com/..."
                           class="form-input @error('google_maps_url') border-red-400 @enderror">
                    @error('google_maps_url')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            @if($locations->isNotEmpty())
            <div class="form-group mb-0">
                <label for="savedLocationSelect" class="form-label">استخدام إحداثيات من موقع محفوظ</label>
                <select id="savedLocationSelect" class="form-input">
                    <option value="">اختر موقعاً محفوظاً</option>
                    @foreach($locations as $savedLocation)
                        <option value="{{ $savedLocation->id }}"
                                data-lat="{{ $savedLocation->latitude }}"
                                data-lng="{{ $savedLocation->longitude }}"
                                data-radius="{{ $savedLocation->radius }}">
                            {{ $savedLocation->name }} ({{ $savedLocation->radius }} متر)
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-xs text-slate-400">يمكنك نسخ الإحداثيات من موقع آخر ثم تعديلها حسب الحاجة.</p>
            </div>
            @endif

            <div class="form-group mb-0">
                <label for="mapSearchInput" class="form-label">بحث عن مكان بالاسم</label>
                <input type="text" id="mapSearchInput"
                       class="form-input"
                       placeholder="مثال: المعادي، القاهرة">
                <p class="mt-1.5 text-xs text-slate-400">اكتب اسم المكان ثم اختره من الاقتراحات لتحديث النقطة على الخريطة.</p>
            </div>

            <div id="location-map" class="rounded-2xl border border-slate-200 overflow-hidden" style="height: 430px;"></div>

            <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                <button type="submit" id="edit-location-submit" class="btn-gold btn-lg flex-1 justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    حفظ التعديلات
                </button>
                <a href="{{ route('locations.index') }}" class="btn-ghost btn-lg">إلغاء</a>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var map;
    var marker;
    var radiusCircle;

    window.initLocationPickerMap = function () {
        var latInput = document.getElementById('latitudeInput');
        var lngInput = document.getElementById('longitudeInput');
        var radiusInput = document.getElementById('radiusInput');
        var mapsUrlInput = document.getElementById('google_maps_url');
        var savedLocationSelect = document.getElementById('savedLocationSelect');
        var searchInput = document.getElementById('mapSearchInput');
        var mapEl = document.getElementById('location-map');
        var geocoder = null;

        if (!latInput || !lngInput || !radiusInput || !mapEl || !window.google || !google.maps) {
            return;
        }

        var initialLat = Number(latInput.value || 30.0444);
        var initialLng = Number(lngInput.value || 31.2357);
        var initialRadius = Number(radiusInput.value || 100);

        var initialCenter = { lat: initialLat, lng: initialLng };

        map = new google.maps.Map(mapEl, {
            center: initialCenter,
            zoom: 14,
            streetViewControl: false,
            mapTypeControl: false,
        });

        marker = new google.maps.Marker({
            map: map,
            position: initialCenter,
            draggable: true,
        });

        radiusCircle = new google.maps.Circle({
            map: map,
            center: initialCenter,
            radius: initialRadius,
            fillColor: '#2563eb',
            fillOpacity: 0.2,
            strokeColor: '#1d4ed8',
            strokeWeight: 2,
        });

        geocoder = new google.maps.Geocoder();

        function setLatLng(latLng) {
            latInput.value = latLng.lat().toFixed(7);
            lngInput.value = latLng.lng().toFixed(7);
            marker.setPosition(latLng);
            radiusCircle.setCenter(latLng);
        }

        function updateRadius() {
            radiusCircle.setRadius(Number(radiusInput.value || 0));
        }

        function extractLatLngFromGoogleMapsUrl(url) {
            if (!url) {
                return null;
            }

            var decoded = decodeURIComponent(url).trim();

            var atMatch = decoded.match(/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/);
            if (atMatch) {
                return { lat: Number(atMatch[1]), lng: Number(atMatch[2]) };
            }

            var qMatch = decoded.match(/[?&](?:q|query)=(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/);
            if (qMatch) {
                return { lat: Number(qMatch[1]), lng: Number(qMatch[2]) };
            }

            var llMatch = decoded.match(/[?&]ll=(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/);
            if (llMatch) {
                return { lat: Number(llMatch[1]), lng: Number(llMatch[2]) };
            }

            var destinationMatch = decoded.match(/[?&]destination=(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/);
            if (destinationMatch) {
                return { lat: Number(destinationMatch[1]), lng: Number(destinationMatch[2]) };
            }

            var dMatch = decoded.match(/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/);
            if (dMatch) {
                return { lat: Number(dMatch[1]), lng: Number(dMatch[2]) };
            }

            return null;
        }

        function extractPlaceQueryFromUrl(url) {
            if (!url) {
                return '';
            }

            var decoded = decodeURIComponent(url);
            var placeMatch = decoded.match(/\/place\/([^\/\?]+)/);
            if (placeMatch && placeMatch[1]) {
                return placeMatch[1].replace(/\+/g, ' ').trim();
            }

            var queryMatch = decoded.match(/[?&](?:q|query|destination)=([^&]+)/);
            if (queryMatch && queryMatch[1]) {
                return queryMatch[1].replace(/\+/g, ' ').trim();
            }

            return '';
        }

        function applyMapByLink(rawUrl) {
            var extracted = extractLatLngFromGoogleMapsUrl(rawUrl || '');
            if (extracted) {
                var latLng = new google.maps.LatLng(extracted.lat, extracted.lng);
                setLatLng(latLng);
                map.panTo(latLng);
                map.setZoom(15);
                return;
            }

            var placeQuery = extractPlaceQueryFromUrl(rawUrl || '');
            if (!placeQuery || !geocoder) {
                return;
            }

            geocoder.geocode({ address: placeQuery }, function (results, status) {
                if (status !== 'OK' || !results || !results.length || !results[0].geometry || !results[0].geometry.location) {
                    return;
                }

                if (searchInput) {
                    searchInput.value = results[0].formatted_address || placeQuery;
                }

                setLatLng(results[0].geometry.location);
                map.panTo(results[0].geometry.location);
                map.setZoom(15);
            });
        }

        map.addListener('click', function (e) {
            setLatLng(e.latLng);
        });

        marker.addListener('dragend', function (e) {
            setLatLng(e.latLng);
        });

        radiusInput.addEventListener('input', updateRadius);

        if (mapsUrlInput) {
            mapsUrlInput.addEventListener('change', function () {
                applyMapByLink(this.value || '');
            });

            mapsUrlInput.addEventListener('blur', function () {
                applyMapByLink(this.value || '');
            });
        }

        if (searchInput && google.maps.places) {
            var autocomplete = new google.maps.places.Autocomplete(searchInput, {
                fields: ['geometry', 'name'],
                types: ['geocode'],
            });

            autocomplete.bindTo('bounds', map);

            autocomplete.addListener('place_changed', function () {
                var place = autocomplete.getPlace();
                if (!place.geometry || !place.geometry.location) {
                    return;
                }

                setLatLng(place.geometry.location);
                map.panTo(place.geometry.location);
                map.setZoom(15);
            });
        }

        if (savedLocationSelect) {
            savedLocationSelect.addEventListener('change', function () {
                var option = this.options[this.selectedIndex];
                if (!option || !option.dataset.lat || !option.dataset.lng) {
                    return;
                }

                var lat = Number(option.dataset.lat);
                var lng = Number(option.dataset.lng);
                var radius = Number(option.dataset.radius || 100);
                var latLng = new google.maps.LatLng(lat, lng);

                setLatLng(latLng);
                radiusInput.value = radius;
                updateRadius();
                map.panTo(latLng);
                map.setZoom(15);
            });
        }
    };
})();
</script>
@php
    $tenant = (string) config('app.tenant', 'eg');
    $tenantMapKeys = config('services.google_maps.api_keys', []);
    $googleMapsApiKey = $tenantMapKeys[$tenant] ?? config('services.google_maps.api_key');
@endphp
@if($googleMapsApiKey)
<script>
window.gm_authFailure = function () {
    var mapEl = document.getElementById('location-map');
    if (!mapEl) {
        return;
    }

    mapEl.classList.add('flex', 'items-center', 'justify-center', 'text-sm', 'text-red-700', 'bg-red-50');
    mapEl.textContent = 'تعذر تحميل خرائط Google لهذا الدومين. تأكد من ضبط GOOGLE_MAPS_API_KEY_EG و GOOGLE_MAPS_API_KEY_SA وتفويض الدومين الحالي داخل Google Cloud.';
};
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&libraries=places&callback=initLocationPickerMap&loading=async" async defer></script>
@else
<script>
document.addEventListener('DOMContentLoaded', function () {
    var mapEl = document.getElementById('location-map');
    if (!mapEl) {
        return;
    }

    mapEl.classList.add('flex', 'items-center', 'justify-center', 'text-sm', 'text-amber-700', 'bg-amber-50');
    mapEl.textContent = 'مفتاح خرائط Google غير مضبوط. الرجاء تعيين GOOGLE_MAPS_API_KEY_EG و GOOGLE_MAPS_API_KEY_SA (أو GOOGLE_MAPS_API_KEY كمفتاح عام).';
});
</script>
@endif
@endpush
