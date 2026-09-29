@php
    $statePath = $getStatePath();
    $bound = $isBoundToCoordinates();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @include('filament.partials.geometry-map', [
        'bound' => $bound,
        'latitudeStatePath' => $bound ? $getLatitudeStatePath() : null,
        'longitudeStatePath' => $bound ? $getLongitudeStatePath() : null,
        'stateExpression' => $bound ? 'null' : $applyStateBindingModifiers("\$wire.\$entangle('{$statePath}')"),
        'type' => $getGeometryType(),
        'mapOptions' => $getMapOptions(),
        'disabled' => $isDisabled(),
        'height' => $getHeight(),
    ])
</x-dynamic-component>
