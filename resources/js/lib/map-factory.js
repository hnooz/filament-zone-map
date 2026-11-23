/**
 * Google Map creation and viewport helpers.
 */
import { resolveMapStyle } from './styling.js'

let sequence = 0

/**
 * The Terra Draw Google adapter requires the map element to have an id.
 *
 * @param {HTMLElement} element
 */
export function ensureElementId(element) {
    if (!element.id) {
        sequence += 1
        element.id = `fi-zone-map-${Date.now().toString(36)}-${sequence}`
    }

    return element.id
}

/**
 * @param {HTMLElement} element
 * @param {typeof google.maps} lib
 * @param {object} config
 * @param {{ isDark: boolean, center?: google.maps.LatLngLiteral, zoom?: number }} view
 * @returns {Promise<google.maps.Map>}
 */
export async function createMap(element, lib, config, { isDark, center, zoom }) {
    const { Map } = await lib.importLibrary('maps')

    ensureElementId(element)
    element.replaceChildren()

    return new Map(element, {
        center: center ?? config.center,
        zoom: zoom ?? config.zoom,
        clickableIcons: false,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
        gestureHandling: 'cooperative',
        ...resolveMapStyle(config, isDark, lib),
    })
}

/**
 * Fits the viewport to all zones. No-op when there are none.
 *
 * @param {google.maps.Map} map
 * @param {typeof google.maps} lib
 * @param {{ features: Array<{ geometry: { coordinates: number[][][] } }> }} collection
 */
export async function fitToZones(map, lib, collection) {
    if (collection.features.length === 0) return

    const { LatLngBounds } = await lib.importLibrary('core')
    const bounds = new LatLngBounds()

    for (const feature of collection.features) {
        for (const [lng, lat] of feature.geometry.coordinates[0]) {
            bounds.extend({ lat, lng })
        }
    }

    map.fitBounds(bounds, 32)
}

/**
 * Removes Google listeners and DOM. Google Maps has no map.destroy().
 *
 * @param {google.maps.Map|null} map
 * @param {typeof google.maps} lib
 * @param {HTMLElement} element
 */
export function disposeMap(map, lib, element) {
    if (map) lib.event.clearInstanceListeners(map)

    element.replaceChildren()
}
