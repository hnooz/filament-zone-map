/**
 * Zone map lifecycle and state sync (Google Map + Terra Draw).
 * Editing actions, theme handling and cleanup live in ZoneMapSession (session.js).
 */
import { createDraw, startDraw, stopDraw } from './draw.js'
import { fromTerraDraw, isPolygonFeature, normalizeState, toTerraDrawFeature, vertexCount } from './geojson.js'
import { loadGoogleMaps } from './loader.js'
import { createMap, disposeMap, fitToZones } from './map-factory.js'
import { hasStyleConflict, isDarkMode, watchTheme } from './styling.js'

const EMIT_DELAY_MS = 120
const noop = () => {}

export class ZoneMapCore {
    /**
     * @param {HTMLElement} element
     * @param {object} config { key, language, region, center, zoom, mapId, styles, zoneColor, readOnly, maxZones, maxVertices }
     * @param {{ onChange?: Function, onSelect?: Function, onModeChange?: Function, onLimit?: Function, onError?: Function }} [callbacks]
     */
    constructor(element, config, callbacks = {}) {
        this.element = element
        this.config = config
        this.callbacks = { onChange: noop, onSelect: noop, onModeChange: noop, onLimit: noop, onError: noop, ...callbacks }
        this.lib = null
        this.map = null
        this.draw = null
        this.passthrough = []
        this.lastJson = null
        this.emitTimer = null
        this.stopWatchingTheme = noop
        this.destroyed = false
    }

    async mount(initialState) {
        if (hasStyleConflict(this.config)) {
            console.warn('[filament-zone-map] Both mapId and styles are set. Google ignores styles when a mapId is used.')
        }

        try {
            this.lib = await loadGoogleMaps(this.config)
            await this.build({ isDark: isDarkMode() }, normalizeState(initialState), true)
            this.stopWatchingTheme = watchTheme((isDark) => this.onThemeChange(isDark))
        } catch (error) {
            this.callbacks.onError(error)
        }
    }

    async build(view, collection, fit) {
        const map = await createMap(this.element, this.lib, this.config, view)
        if (this.destroyed) return disposeMap(map, this.lib, this.element)

        this.map = map
        this.draw = createDraw({ lib: this.lib, map, color: this.config.zoneColor })
        await startDraw(this.draw, map, this.lib)
        if (this.destroyed) return

        this.bindEvents(this.draw)
        this.load(collection)
        if (fit) await fitToZones(this.map, this.lib, this.currentState())
        this.setMode(this.config.readOnly ? 'static' : 'select')
    }

    bindEvents(draw) {
        const isCurrent = () => !this.destroyed && draw === this.draw

        draw.on('change', (_ids, type) => isCurrent() && type !== 'styling' && this.scheduleEmit())
        draw.on('finish', (id, context) => isCurrent() && this.onFinish(id, context))
        draw.on('select', (id) => isCurrent() && this.callbacks.onSelect(String(id)))
        draw.on('deselect', () => isCurrent() && this.callbacks.onSelect(null))
    }

    /** Replaces all zones. Zones Terra Draw rejects are kept untouched (never silently lost). */
    load(collection) {
        this.draw.clear()
        this.passthrough = []

        const sources = collection.features.filter(isPolygonFeature)
        const results = this.draw.addFeatures(sources.map(toTerraDrawFeature))

        results.forEach((result, index) => {
            if (!result.valid) this.passthrough.push(sources[index])
        })

        if (this.passthrough.length > 0) {
            console.warn('[filament-zone-map] Some zones could not be edited and were kept as-is.', results.filter((r) => !r.valid))
        }

        this.lastJson = JSON.stringify(this.currentState())
    }

    /** External update (Livewire). Ignored when it echoes what we just emitted. */
    setState(state) {
        if (!this.draw || this.destroyed) return

        const collection = normalizeState(state)
        if (JSON.stringify(collection) === this.lastJson) return

        this.load(collection)
    }

    currentState() {
        const collection = fromTerraDraw(this.draw.getSnapshot())
        collection.features.push(...this.passthrough)

        return collection
    }

    scheduleEmit() {
        clearTimeout(this.emitTimer)
        this.emitTimer = setTimeout(() => this.emit(), EMIT_DELAY_MS)
    }

    emit() {
        if (!this.draw || this.destroyed) return

        const state = this.currentState()
        const json = JSON.stringify(state)
        if (json === this.lastJson) return

        this.lastJson = json
        this.callbacks.onChange(state)
    }

    onFinish(id, context) {
        const feature = this.draw.getSnapshotFeature(id)
        const { maxZones, maxVertices } = this.config

        if (context.action === 'draw') {
            if (maxZones && this.currentState().features.length > maxZones) return this.reject(id, 'max_zones', maxZones)
            if (maxVertices && feature && vertexCount(feature) > maxVertices) return this.reject(id, 'max_vertices', maxVertices)

            this.draw.selectFeature(id)
            this.callbacks.onModeChange('select')
        } else if (maxVertices && feature && vertexCount(feature) > maxVertices) {
            this.callbacks.onLimit('max_vertices', maxVertices)
        }

        this.scheduleEmit()
    }

    reject(id, type, max) {
        this.draw.removeFeatures([id])
        this.callbacks.onLimit(type, max)
    }
}
