@php
    use Filament\Support\Facades\FilamentAsset;

    $statePath = $getStatePath();
    $bound = $isBoundToCoordinates();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div wire:ignore x-load x-load-src="{{ FilamentAsset::getAlpineComponentSrc('geometry-map', 'app') }}"
        x-data="geometryMap({
            bound: @js($bound),
        
            @if ($bound) latitude: $wire.$entangle('{{ $getLatitudeStatePath() }}'),
                longitude: $wire.$entangle('{{ $getLongitudeStatePath() }}'),
            @else
                state: {{ $applyStateBindingModifiers("\$wire.\$entangle('{$statePath}')") }}, @endif
        
            type: @js($getGeometryType()),
            map: @js($getMapOptions()),
            disabled: @js($isDisabled()),
        })">
        <div x-ref="map" class="w-full overflow-hidden rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20"
            style="
                height: {{ $getHeight() }}px;
                isolation: isolate;
            ">
        </div>
    </div>
</x-dynamic-component>
