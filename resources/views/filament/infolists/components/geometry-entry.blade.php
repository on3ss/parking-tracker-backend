@php
    use Filament\Support\Facades\FilamentAsset;
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div wire:ignore x-load x-load-src="{{ FilamentAsset::getAlpineComponentSrc('geometry-picker', 'app') }}"
        x-data="geometryPicker({
            state: @js($getGeoJson()),
            type: null,
            center: @js($getCenter()),
            zoom: @js($getZoom()),
            disabled: true,
        })">
        <div x-ref="map" class="w-full overflow-hidden rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20"
            style="height: {{ $getHeight() }}px; isolation: isolate;"></div>
    </div>
</x-dynamic-component>
