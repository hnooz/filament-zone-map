<div x-cloak x-show="selectedId" class="fi-zone-map-editor" role="group" aria-label="{{ __('filament-zone-map::zone-map.editor.title') }}">
    <p class="fi-zone-map-editor-title">{{ __('filament-zone-map::zone-map.editor.title') }}</p>

    <label class="fi-zone-map-editor-field">
        <span>{{ __('filament-zone-map::zone-map.editor.name') }}</span>
        <input
            type="text"
            class="fi-input fi-zone-map-input"
            x-model="zoneName"
            x-on:input.debounce.300ms="updateName()"
            x-on:keydown.enter.prevent
            x-bind:maxlength="config.maxNameLength"
            placeholder="{{ __('filament-zone-map::zone-map.editor.name_placeholder') }}"
        />
    </label>

    <div class="fi-zone-map-editor-field">
        <span>{{ __('filament-zone-map::zone-map.editor.color') }}</span>
        <div class="fi-zone-map-swatches" role="radiogroup" aria-label="{{ __('filament-zone-map::zone-map.editor.color') }}">
            <template x-for="color in config.colors" x-bind:key="color">
                <button
                    type="button"
                    role="radio"
                    class="fi-zone-map-swatch"
                    x-bind:style="{ backgroundColor: color }"
                    x-bind:aria-checked="zoneColor === color"
                    x-bind:aria-label="color"
                    x-bind:class="{ 'fi-active': zoneColor === color }"
                    x-on:click="pickColor(color)"
                ></button>
            </template>
        </div>
    </div>
</div>
