@props([
    'bound' => false,
    'latitudeStatePath' => null,
    'longitudeStatePath' => null,
    'stateExpression' => 'null',
    'type' => null,
    'mapOptions' => [],
    'disabled' => false,
    'height' => 400,
])

<div wire:ignore x-data="geometryMap({
    bound: @js($bound),

    @if ($bound) latitude: $wire.$entangle('{{ $latitudeStatePath }}'),
            longitude: $wire.$entangle('{{ $longitudeStatePath }}'),
        @else
            state: {!! $stateExpression !!}, @endif

    type: @js($type),
    map: @js($mapOptions),
    disabled: @js($disabled),
})">
    <div x-ref="map" class="w-full overflow-hidden rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20"
        style="height: {{ $height }}px; isolation: isolate;"></div>
</div>
