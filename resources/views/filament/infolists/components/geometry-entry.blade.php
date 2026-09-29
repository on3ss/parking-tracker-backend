@php
    use Illuminate\Support\Js;
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @include('filament.partials.geometry-map', [
        'bound' => false,
        'stateExpression' => (string) Js::from($getGeoJson()),
        'type' => null,
        'mapOptions' => $getMapOptions(),
        'disabled' => true,
        'height' => $getHeight(),
    ])
</x-dynamic-component>
