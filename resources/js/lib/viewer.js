/**
 * Read-only zone map on google.maps.Data (no Terra Draw: much smaller bundle).
 * Used by ZoneMapEntry and ZonesMapWidget.
 */
import { isPolygonFeature, normalizeState } from './geojson.js'
import { buildInfoContent } from './info-content.js'
import { loadGoogleMaps } from './loader.js'
import { createMap, disposeMap, fitToZones } from './map-factory.js'
import { hasStyleConflict, isDarkMode, resolveMapStyle, watchTheme } from './styling.js'

const noop = () => {}

/** Groups [{ key, label, url, zones }] -> one FeatureCollection with unique ids and group info. */
export function flattenGroups(groups) {
    const features = []

    for (const group of groups ?? []) {
        for (const feature of normalizeState(group.zones).features.filter(isPolygonFeature)) {
            features.push({
                ...feature,
                id: `${group.key}:${feature.id ?? features.length}`,
                properties: { ...feature.properties, groupLabel: group.label ?? null, url: group.url ?? null },
            })
        }
    }

    return { type: 'FeatureCollection', features }
}

export class ZoneViewerSession {
    constructor(element, config, labels, callbacks = {}) {
        this.element = element
        this.config = config
        this.labels = labels
        this.onError = callbacks.onError ?? noop
        this.collection = { type: 'FeatureCollection', features: [] }
        this.lib = null
        this.map = null
        this.data = null
        this.infoWindow = null
        this.stopWatchingTheme = noop
        this.destroyed = false
    }

    async mount(groups) {
        if (hasStyleConflict(this.config)) {
            console.warn('[filament-zone-map] Both mapId and styles are set. Google ignores styles when a mapId is used.')
        }

        this.collection = flattenGroups(groups)

        try {
            this.lib = await loadGoogleMaps(this.config)
            await this.build({ isDark: isDarkMode() }, this.config.fitBounds !== false)
            this.stopWatchingTheme = watchTheme((isDark) => this.onThemeChange(isDark))
        } catch (error) {
            this.onError(error)
        }
    }

    async build(view, fit) {
        const map = await createMap(this.element, this.lib, this.config, view)
        if (this.destroyed) return disposeMap(map, this.lib, this.element)

        const { Data, InfoWindow } = await this.lib.importLibrary('maps')
        const color = this.config.zoneColor
        const zoneColor = (feature) => feature.getProperty('color') ?? color

        this.map = map
        this.infoWindow = new InfoWindow()
        this.data = new Data({ map })
        this.data.addGeoJson(this.collection)
        this.data.setStyle((feature) => ({
            fillColor: zoneColor(feature),
            strokeColor: zoneColor(feature),
            fillOpacity: 0.25,
            strokeWeight: 2,
            clickable: true,
        }))

        this.data.addListener('mouseover', (event) => this.data.overrideStyle(event.feature, { fillOpacity: 0.4, strokeWeight: 3 }))
        this.data.addListener('mouseout', () => this.data.revertStyle())
        this.data.addListener('click', (event) => this.openInfo(event))

        if (fit) await fitToZones(map, this.lib, this.collection)
    }

    openInfo(event) {
        const zone = {
            name: event.feature.getProperty('name'),
            groupLabel: event.feature.getProperty('groupLabel'),
            url: event.feature.getProperty('url'),
            color: event.feature.getProperty('color') ?? this.config.zoneColor,
        }

        this.infoWindow.setContent(buildInfoContent(document, zone, this.labels))
        this.infoWindow.setPosition(event.latLng)
        this.infoWindow.open({ map: this.map })
    }

    refresh() {
        if (this.map && this.lib) this.lib.event.trigger(this.map, 'resize')
    }

    onThemeChange(isDark) {
        if (!this.map || this.destroyed) return

        if (!this.config.mapId) {
            this.map.setOptions({ styles: resolveMapStyle(this.config, isDark, this.lib).styles })
            return
        }

        const center = this.map.getCenter()?.toJSON()
        const zoom = this.map.getZoom()
        this.teardown()
        this.build({ isDark, center, zoom }, false).catch((error) => this.onError(error))
    }

    teardown() {
        this.infoWindow?.close()
        if (this.data) {
            this.lib.event.clearInstanceListeners(this.data)
            this.data.setMap(null)
        }
        if (this.lib) disposeMap(this.map, this.lib, this.element)

        this.data = null
        this.map = null
        this.infoWindow = null
    }

    destroy() {
        if (this.destroyed) return

        this.destroyed = true
        this.stopWatchingTheme()
        this.teardown()
    }
}
