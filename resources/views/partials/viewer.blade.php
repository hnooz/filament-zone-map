{{--
    Read-only map shared by ZoneMapEntry and ZonesMapWidget.
    Expects: $groups (list), $config (array), $labels (array), $height (sanitised CSS length), $label (string|null)
--}}
@php
    use Filament\Support\Facades\FilamentAsset;
    use Hnooz\FilamentZoneMap\FilamentZoneMapServiceProvider;

    $assetPackage = FilamentZoneMapServiceProvider::ASSET_PACKAGE;
@endphp

<div
    x-load
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('zone-viewer', $assetPackage) }}"
    x-load-css="[@js(FilamentAsset::getStyleHref('zone-map', $assetPackage))]"
    x-data="zoneViewerComponent({
        groups: @js($groups),
        config: @js($config),
        labels: @js($labels),
    })"
    wire:key="zone-viewer.{{ md5(serialize([$groups, $config])) }}"
    style="--fi-zone-map-height: {{ $height }}"
    class="fi-zone-map fi-zone-map-read-only"
>
    <div class="fi-zone-map-body">
        <div wire:ignore x-ref="map" class="fi-zone-map-canvas" role="img" aria-label="{{ $label }}"></div>
    </div>

    <p x-cloak x-show="error" x-text="error" role="alert" class="fi-zone-map-message fi-zone-map-error"></p>
</div>
