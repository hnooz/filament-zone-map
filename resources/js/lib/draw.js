/**
 * Terra Draw setup for Google Maps: modes, per-zone colors and select-mode edit rules.
 *
 * All three drawing modes are always registered so stored zones of any shape render
 * and stay editable; the toolbar decides which shapes users can draw.
 */
import {
    TerraDraw,
    TerraDrawCircleMode,
    TerraDrawPolygonMode,
    TerraDrawRectangleMode,
    TerraDrawSelectMode,
} from 'terra-draw'
import { TerraDrawGoogleMapsAdapter } from 'terra-draw-google-maps-adapter'
import { COORDINATE_PRECISION, createId } from './geojson.js'

const MAX_ID_LENGTH = 64

/** Accept stored ids like "zone-1" or "7", not only UUIDs (Terra Draw's default). */
const idStrategy = {
    isValidId: (id) => (typeof id === 'string' || typeof id === 'number') && String(id).length > 0 && String(id).length <= MAX_ID_LENGTH,
    getId: createId,
}

/**
 * @param {{ lib: typeof google.maps, map: google.maps.Map, color: string }} options
 * @returns {TerraDraw}
 */
export function createDraw({ lib, map, color }) {
    const fill = (feature) => feature.properties?.color ?? color

    const shapeStyles = { fillColor: fill, outlineColor: fill, fillOpacity: 0.25, outlineWidth: 2 }

    const select = new TerraDrawSelectMode({
        flags: {
            polygon: { feature: { draggable: true, coordinates: { draggable: true, midpoints: true, deletable: true } } },
            rectangle: { feature: { draggable: true, coordinates: { resizable: 'opposite' } } },
            circle: { feature: { draggable: true, coordinates: { resizable: 'center-fixed' } } },
        },
        styles: {
            selectedPolygonColor: fill,
            selectedPolygonOutlineColor: fill,
            selectedPolygonFillOpacity: 0.4,
            selectedPolygonOutlineWidth: 3,
            selectionPointColor: fill,
            midPointColor: fill,
        },
    })

    return new TerraDraw({
        adapter: new TerraDrawGoogleMapsAdapter({
            lib,
            map,
            coordinatePrecision: COORDINATE_PRECISION,
            isolatedData: true,
        }),
        modes: [
            new TerraDrawPolygonMode({ styles: shapeStyles }),
            new TerraDrawRectangleMode({ styles: shapeStyles }),
            new TerraDrawCircleMode({ styles: shapeStyles }),
            select,
        ],
        idStrategy,
    })
}

/**
 * Starts Terra Draw once the map projection exists and resolves on Terra Draw's `ready`
 * (required by the Google adapter before setMode / addFeatures).
 *
 * @param {TerraDraw} draw
 * @param {google.maps.Map} map
 * @param {typeof google.maps} lib
 * @returns {Promise<void>}
 */
export function startDraw(draw, map, lib) {
    return new Promise((resolve) => {
        draw.on('ready', () => resolve())

        if (map.getProjection()) {
            draw.start()
            return
        }

        lib.event.addListenerOnce(map, 'projection_changed', () => draw.start())
    })
}

/**
 * @param {TerraDraw} draw
 */
export function stopDraw(draw) {
    try {
        if (draw.enabled) draw.stop()
    } catch {
        // Already stopped or adapter gone (element removed by Livewire).
    }
}
