@php
    $icons = [
        'polygon' => '<path d="M12 3l8 6-3 11H7L4 9z" />',
        'rectangle' => '<rect x="4" y="6" width="16" height="12" rx="1" />',
        'circle' => '<circle cx="12" cy="12" r="8" />',
        'select' => '<path d="M5 3l14 8-6 2-2 6z" />',
    ];
@endphp

<div class="fi-zone-map-toolbar" role="toolbar" aria-label="{{ __('filament-zone-map::zone-map.toolbar.label') }}">
    @foreach ([...$shapes, 'select'] as $toolMode)
        <button
            type="button"
            class="fi-zone-map-tool"
            x-on:click="setMode(@js($toolMode))"
            x-bind:class="{ 'fi-active': mode === @js($toolMode) }"
            x-bind:aria-pressed="mode === @js($toolMode)"
            title="{{ __("filament-zone-map::zone-map.modes.{$toolMode}") }}"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
                {!! $icons[$toolMode] !!}
            </svg>
            <span class="fi-zone-map-tool-label">{{ __("filament-zone-map::zone-map.modes.{$toolMode}") }}</span>
        </button>
    @endforeach

    <span class="fi-zone-map-toolbar-spacer"></span>

    <button
        type="button"
        class="fi-zone-map-tool fi-zone-map-tool-danger"
        x-show="selectedId"
        x-cloak
        x-on:click="deleteSelected()"
        title="{{ __('filament-zone-map::zone-map.actions.delete') }}"
    >
        <x-filament::icon icon="heroicon-m-trash" class="fi-zone-map-icon" />
        <span class="fi-zone-map-tool-label">{{ __('filament-zone-map::zone-map.actions.delete') }}</span>
    </button>

    <button
        type="button"
        class="fi-zone-map-tool fi-zone-map-tool-danger"
        x-show="hasZones"
        x-cloak
        x-on:click="clearAll()"
        x-bind:class="{ 'fi-active': isConfirmingClear }"
        x-bind:title="isConfirmingClear ? @js(__('filament-zone-map::zone-map.actions.confirm_clear')) : @js(__('filament-zone-map::zone-map.actions.clear'))"
    >
        <x-filament::icon icon="heroicon-m-x-mark" class="fi-zone-map-icon" />
        <span
            class="fi-zone-map-tool-label"
            x-text="isConfirmingClear ? @js(__('filament-zone-map::zone-map.actions.confirm_clear')) : @js(__('filament-zone-map::zone-map.actions.clear'))"
        ></span>
    </button>
</div>
