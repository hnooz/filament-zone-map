@php
    use Filament\Support\Facades\FilamentAsset;
    use Hnooz\FilamentZoneMap\FilamentZoneMapServiceProvider;

    $assetPackage = FilamentZoneMapServiceProvider::ASSET_PACKAGE;
    $statePath = $getStatePath();
    $config = $getMapConfig();
    $isReadOnly = $config['readOnly'];
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('zone-map', $assetPackage) }}"
        x-load-css="[@js(FilamentAsset::getStyleHref('zone-map', $assetPackage))]"
        x-data="zoneMapFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            config: @js($config),
            labels: @js($getJsLabels()),
        })"
        wire:key="{{ $getLivewireKey() }}.{{ md5(serialize([$isReadOnly, $config['shapes'], $config['editableZones']])) }}"
        style="--fi-zone-map-height: {{ $getHeight() }}"
        {{ $getExtraAttributeBag()->class(['fi-zone-map', 'fi-zone-map-read-only' => $isReadOnly]) }}
    >
        @unless ($isReadOnly)
            @include('filament-zone-map::forms.partials.toolbar', ['shapes' => $config['shapes']])
        @endunless

        <div class="fi-zone-map-body">
            <div wire:ignore x-ref="map" class="fi-zone-map-canvas" role="application" aria-label="{{ $getLabel() }}"></div>

            @if ($config['editableZones'] && ! $isReadOnly)
                @include('filament-zone-map::forms.partials.editor')
            @endif
        </div>

        <p x-cloak x-show="message" x-text="message" role="status" class="fi-zone-map-message"></p>
        <p x-cloak x-show="error" x-text="error" role="alert" class="fi-zone-map-message fi-zone-map-error"></p>
    </div>
</x-dynamic-component>
