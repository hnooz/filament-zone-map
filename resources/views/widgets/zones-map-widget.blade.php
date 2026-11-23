<x-filament-widgets::widget class="fi-zone-map-widget">
    <x-filament::section :heading="$heading">
        @include('filament-zone-map::partials.viewer', [
            'groups' => $groups,
            'config' => $config,
            'labels' => $labels,
            'height' => $height,
            'label' => is_string($heading) ? $heading : null,
        ])
    </x-filament::section>
</x-filament-widgets::widget>
