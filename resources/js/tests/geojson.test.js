import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import {
    circleRadiusMeters,
    createId,
    fromTerraDraw,
    haversineMeters,
    normalizeState,
    toTerraDrawFeature,
    vertexCount,
} from '../lib/geojson.js'

const square = [[[46.67, 24.71], [46.7, 24.71], [46.7, 24.74], [46.67, 24.74], [46.67, 24.71]]]

const feature = (properties = {}, extra = {}) => ({
    type: 'Feature',
    id: 'zone-1',
    geometry: { type: 'Polygon', coordinates: square },
    properties: { shape: 'polygon', name: 'North', color: '#E74C3C', radius_m: null, ...properties },
    ...extra,
})

/** Circle polygon around Riyadh with a known radius, like Terra Draw produces. */
function circleRing(radiusM, steps = 64) {
    const [lng0, lat0] = [46.68, 24.72]
    const ring = []
    for (let i = 0; i < steps; i++) {
        const angle = (2 * Math.PI * i) / steps
        const dLat = (radiusM * Math.cos(angle)) / 111320
        const dLng = (radiusM * Math.sin(angle)) / (111320 * Math.cos((lat0 * Math.PI) / 180))
        ring.push([lng0 + dLng, lat0 + dLat])
    }
    ring.push(ring[0])
    return [ring]
}

describe('normalizeState', () => {
    it('accepts objects and JSON strings', () => {
        const collection = { type: 'FeatureCollection', features: [feature()] }
        assert.deepEqual(normalizeState(collection), collection)
        assert.deepEqual(normalizeState(JSON.stringify(collection)), collection)
    })

    it('returns an empty collection for anything invalid', () => {
        for (const value of [null, undefined, '', '{bad', '"x"', [], { type: 'Feature' }, { type: 'FeatureCollection', features: {} }]) {
            assert.deepEqual(normalizeState(value), { type: 'FeatureCollection', features: [] })
        }
    })
})

describe('toTerraDrawFeature', () => {
    it('maps shape to mode and keeps name/color', () => {
        const result = toTerraDrawFeature(feature({ shape: 'rectangle' }))
        assert.equal(result.id, 'zone-1')
        assert.deepEqual(result.properties, { mode: 'rectangle', name: 'North', color: '#E74C3C' })
    })

    it('rounds coordinates to 7 decimals', () => {
        const coords = [[[46.123456789, 24.1], [46.2, 24.1], [46.2, 24.2], [46.123456789, 24.1]]]
        const result = toTerraDrawFeature(feature({}, { geometry: { type: 'Polygon', coordinates: coords } }))
        assert.deepEqual(result.geometry.coordinates[0][0], [46.1234568, 24.1])
    })

    it('stringifies numeric ids and generates missing ones', () => {
        assert.equal(toTerraDrawFeature(feature({}, { id: 7 })).id, '7')
        assert.match(toTerraDrawFeature(feature({}, { id: undefined })).id, /^[0-9a-f-]{36}$/)
    })

    it('maps freehand zones to Terra Draw freehand mode', () => {
        assert.equal(toTerraDrawFeature(feature({ shape: 'freehand' })).properties.mode, 'freehand')
    })

    it('defaults unknown shapes to polygon and sets radiusKilometers for circles', () => {
        assert.equal(toTerraDrawFeature(feature({ shape: 'hexagon' })).properties.mode, 'polygon')
        assert.equal(toTerraDrawFeature(feature({ shape: 'circle', radius_m: 1500 })).properties.radiusKilometers, 1.5)
    })
})

describe('fromTerraDraw', () => {
    const terra = (properties, geometry = { type: 'Polygon', coordinates: square }) => ({ type: 'Feature', id: 'a', geometry, properties })

    it('returns the data contract with property order shape, name, color, radius_m', () => {
        const result = fromTerraDraw([terra({ mode: 'polygon', name: 'N', color: '#112233', selected: true, edited: false })])
        assert.deepEqual(result, {
            type: 'FeatureCollection',
            features: [{ type: 'Feature', id: 'a', geometry: { type: 'Polygon', coordinates: square }, properties: { shape: 'polygon', name: 'N', color: '#112233', radius_m: null } }],
        })
        assert.deepEqual(Object.keys(result.features[0].properties), ['shape', 'name', 'color', 'radius_m'])
    })

    it('keeps freehand zones with a null radius', () => {
        const result = fromTerraDraw([terra({ mode: 'freehand', name: 'Old town' })])
        assert.deepEqual(result.features[0].properties, { shape: 'freehand', name: 'Old town', color: null, radius_m: null })
    })

    it('drops guidance points, in-progress drawings and unknown modes', () => {
        const snapshot = [
            terra({ mode: 'select', selectionPoint: true }, { type: 'Point', coordinates: [46.6, 24.7] }),
            terra({ mode: 'polygon', currentlyDrawing: true }),
            terra({ mode: 'sector' }),
        ]
        assert.equal(fromTerraDraw(snapshot).features.length, 0)
    })

    it('derives circle radius from geometry', () => {
        const result = fromTerraDraw([terra({ mode: 'circle', radiusKilometers: 99 }, { type: 'Polygon', coordinates: circleRing(1500) })])
        assert.ok(Math.abs(result.features[0].properties.radius_m - 1500) < 15)
    })
})

describe('geometry helpers', () => {
    it('counts vertices without closing points', () => {
        assert.equal(vertexCount(feature()), 4)
    })

    it('measures distances and circle radii', () => {
        assert.ok(Math.abs(haversineMeters([46.6753, 24.7136], [46.6753, 24.7236]) - 1111.95) < 1)
        assert.ok(Math.abs(circleRadiusMeters(circleRing(800)) - 800) < 10)
    })

    it('creates RFC 4122 v4 ids', () => {
        assert.match(createId(), /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/)
    })
})
