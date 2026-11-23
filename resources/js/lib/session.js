/**
 * Public API used by the Alpine components.
 *
 *   const session = new ZoneMapSession(el, config, { onChange, onSelect, onModeChange, onLimit, onError })
 *   await session.mount(state)
 *   session.setMode('polygon' | 'rectangle' | 'circle' | 'select' | 'static')
 *   session.setState(state)        // from Livewire
 *   session.updateZone(id, { name, color })
 *   session.deleteSelected() / session.clear()
 *   session.destroy()              // on Livewire/Alpine teardown
 */
import { ZoneMapCore } from './core.js'
import { stopDraw } from './draw.js'
import { SHAPES } from './geojson.js'
import { disposeMap } from './map-factory.js'
import { resolveMapStyle } from './styling.js'

const MODES = [...SHAPES, 'select', 'static']

export class ZoneMapSession extends ZoneMapCore {
    get mode() {
        return this.draw?.getMode() ?? null
    }

    setMode(mode) {
        if (!this.draw || this.destroyed || !MODES.includes(mode)) return

        const target = this.config.readOnly ? 'static' : mode
        if (this.draw.getMode() !== target) this.draw.setMode(target)

        this.callbacks.onModeChange(target)
    }

    selectedId() {
        if (!this.draw) return null

        const selected = this.draw.getSnapshot().find((f) => f.properties?.selected === true)

        return selected ? String(selected.id) : null
    }

    /**
     * @param {string} id
     * @param {{ name?: string|null, color?: string|null }} properties
     */
    updateZone(id, { name, color } = {}) {
        if (!this.draw || this.config.readOnly || !this.draw.getSnapshotFeature(id)) return

        const changes = {}
        if (name !== undefined) changes.name = name === '' ? null : name
        if (color !== undefined) changes.color = color

        this.draw.updateFeatureProperties(id, changes)
        this.scheduleEmit()
    }

    deleteSelected() {
        const id = this.selectedId()
        if (!id || this.config.readOnly) return

        this.draw.deselectFeature(id)
        this.draw.removeFeatures([id])
        this.callbacks.onSelect(null)
        this.scheduleEmit()
    }

    clear() {
        if (!this.draw || this.config.readOnly) return

        this.draw.clear()
        this.passthrough = []
        this.callbacks.onSelect(null)
        this.setMode('select')
        this.scheduleEmit()
    }

    /** Call after the container becomes visible (tabs, modals) or changes size. */
    refresh() {
        if (this.map && this.lib) this.lib.event.trigger(this.map, 'resize')
    }

    onThemeChange(isDark) {
        if (!this.map || this.destroyed) return

        if (!this.config.mapId) {
            this.map.setOptions({ styles: resolveMapStyle(this.config, isDark, this.lib).styles })
            return
        }

        // colorScheme is fixed at map creation: rebuild, keeping zones and viewport.
        this.rebuild(isDark).catch((error) => this.callbacks.onError(error))
    }

    async rebuild(isDark) {
        clearTimeout(this.emitTimer)
        this.emit()

        const state = this.currentState()
        const mode = this.mode
        const center = this.map.getCenter()?.toJSON()
        const zoom = this.map.getZoom()

        stopDraw(this.draw)
        disposeMap(this.map, this.lib, this.element)
        this.draw = null
        this.map = null

        await this.build({ isDark, center, zoom }, state, false)

        if (mode && mode !== 'static') this.setMode(mode)
    }

    destroy() {
        if (this.destroyed) return

        this.destroyed = true
        clearTimeout(this.emitTimer)
        this.stopWatchingTheme()

        if (this.draw) stopDraw(this.draw)
        if (this.lib) disposeMap(this.map, this.lib, this.element)

        this.draw = null
        this.map = null
    }
}
