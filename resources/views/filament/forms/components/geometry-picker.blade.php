@php
    use Filament\Support\Facades\FilamentAsset;

    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div wire:ignore x-load x-load-src="{{ FilamentAsset::getAlpineComponentSrc('geometry-picker', 'app') }}"
        x-data="geometryPicker({
            state: {{ $applyStateBindingModifiers("\$wire.\$entangle('{$statePath}')") }},
            type: @js($getGeometryType()),
            center: @js($getCenter()),
            zoom: @js($getZoom()),
            disabled: @js($isDisabled()),
        })">
        <div x-ref="map" class="w-full overflow-hidden rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20"
            style="height: {{ $getHeight() }}px; isolation: isolate;"></div>
    </div>
</x-dynamic-component>
