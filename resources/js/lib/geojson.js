/**
 * Converts between the package data contract (see INSTRUCTIONS.md) and Terra Draw features.
 * Pure functions: no DOM, no Google Maps.
 */
export const SHAPES = ['polygon', 'rectangle', 'circle']
export const COORDINATE_PRECISION = 7

const EARTH_RADIUS_M = 6371008.8
const toRad = (deg) => (deg * Math.PI) / 180
const round = (value) => Number(value.toFixed(COORDINATE_PRECISION))

export const emptyCollection = () => ({ type: 'FeatureCollection', features: [] })

/**
 * UUID v4 that also works outside secure contexts (crypto.randomUUID needs https).
 */
export function createId() {
    if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID()

    const bytes = globalThis.crypto.getRandomValues(new Uint8Array(16))
    bytes[6] = (bytes[6] & 0x0f) | 0x40
    bytes[8] = (bytes[8] & 0x3f) | 0x80
    const hex = [...bytes].map((b) => b.toString(16).padStart(2, '0')).join('')

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`
}

/**
 * Livewire state (object, JSON string or null) -> FeatureCollection. Never throws.
 */
export function normalizeState(state) {
    let value = state

    if (typeof value === 'string') {
        try {
            value = JSON.parse(value)
        } catch {
            return emptyCollection()
        }
    }

    if (value?.type !== 'FeatureCollection' || !Array.isArray(value.features)) {
        return emptyCollection()
    }

    return { type: 'FeatureCollection', features: value.features }
}

export function isPolygonFeature(feature) {
    return feature?.type === 'Feature' && feature.geometry?.type === 'Polygon' && Array.isArray(feature.geometry.coordinates)
}

/**
 * Contract feature -> Terra Draw feature (mode = shape, coordinates rounded to the store precision).
 */
export function toTerraDrawFeature(feature) {
    const props = feature.properties ?? {}
    const shape = SHAPES.includes(props.shape) ? props.shape : 'polygon'
    const rings = feature.geometry.coordinates.map((ring) => ring.map(([lng, lat]) => [round(lng), round(lat)]))
    const properties = { mode: shape, name: props.name ?? null, color: props.color ?? null }

    if (shape === 'circle') {
        properties.radiusKilometers = (props.radius_m ?? circleRadiusMeters(rings)) / 1000
    }

    const id = typeof feature.id === 'string' || typeof feature.id === 'number' ? String(feature.id) : createId()

    return { type: 'Feature', id, geometry: { type: 'Polygon', coordinates: rings }, properties }
}

/**
 * Terra Draw snapshot -> contract FeatureCollection.
 * Drops guidance points, in-progress drawings and Terra Draw's internal properties.
 */
export function fromTerraDraw(snapshot) {
    const features = snapshot
        .filter((f) => isPolygonFeature(f) && SHAPES.includes(f.properties?.mode) && !f.properties.currentlyDrawing)
        .map((f) => {
            const shape = f.properties.mode

            return {
                type: 'Feature',
                id: String(f.id),
                geometry: { type: 'Polygon', coordinates: f.geometry.coordinates },
                properties: {
                    shape,
                    name: f.properties.name ?? null,
                    color: f.properties.color ?? null,
                    radius_m: shape === 'circle' ? Math.round(circleRadiusMeters(f.geometry.coordinates) * 100) / 100 : null,
                },
            }
        })

    return { type: 'FeatureCollection', features }
}

/**
 * Distinct vertices across all rings (closing points excluded). Matches the PHP validator.
 */
export function vertexCount(feature) {
    return feature.geometry.coordinates.reduce((sum, ring) => sum + Math.max(ring.length - 1, 0), 0)
}

/**
 * Radius derived from geometry (mean distance from the centroid to the outer ring),
 * so it stays correct after the circle is resized in select mode.
 */
export function circleRadiusMeters(rings) {
    const ring = rings[0].slice(0, -1)
    if (ring.length === 0) return 0

    const center = [
        ring.reduce((sum, [lng]) => sum + lng, 0) / ring.length,
        ring.reduce((sum, [, lat]) => sum + lat, 0) / ring.length,
    ]

    return ring.reduce((sum, point) => sum + haversineMeters(center, point), 0) / ring.length
}

export function haversineMeters([lng1, lat1], [lng2, lat2]) {
    const dLat = toRad(lat2 - lat1)
    const dLng = toRad(lng2 - lng1)
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2

    return 2 * EARTH_RADIUS_M * Math.asin(Math.sqrt(a))
}
