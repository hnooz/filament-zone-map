@php
    $groups = $getZoneGroups();
    $placeholder = $getPlaceholder();
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div {{ $getExtraAttributeBag() }}>
        @if ($groups === [] && filled($placeholder))
            <div class="fi-in-placeholder">{{ $placeholder }}</div>
        @else
            @include('filament-zone-map::partials.viewer', [
                'groups' => $groups,
                'config' => $getViewerConfig(),
                'labels' => $getJsLabels(),
                'height' => $getHeight(),
                'label' => $getLabel(),
            ])
        @endif
    </div>
</x-dynamic-component>
