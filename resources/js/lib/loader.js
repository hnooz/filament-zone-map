/**
 * Loads the Google Maps JavaScript API once per page.
 *
 * zone-map.js and zone-viewer.js are separate bundles, so the shared promise
 * lives on `window`, not in module scope.
 */
const GLOBAL_KEY = '__filamentZoneMapGoogleMaps'
const CALLBACK_KEY = `${GLOBAL_KEY}Ready`
const API_URL = 'https://maps.googleapis.com/maps/api/js'

/**
 * @param {{ key?: string|null, language?: string|null, region?: string|null, version?: string }} options
 * @param {{ win?: Window, doc?: Document }} [env] Injectable for tests.
 * @returns {Promise<typeof google.maps>}
 */
export function loadGoogleMaps(options, { win = window, doc = document } = {}) {
    if (win.google?.maps?.importLibrary) {
        return Promise.resolve(win.google.maps)
    }

    if (win[GLOBAL_KEY]) {
        return win[GLOBAL_KEY]
    }

    if (!options.key) {
        return Promise.reject(new Error('[filament-zone-map] Google Maps browser key is missing.'))
    }

    win[GLOBAL_KEY] = new Promise((resolve, reject) => {
        win[CALLBACK_KEY] = () => {
            delete win[CALLBACK_KEY]
            resolve(win.google.maps)
        }

        const script = doc.createElement('script')
        script.src = buildUrl(options)
        script.async = true

        const nonce = doc.querySelector('script[nonce]')?.nonce
        if (nonce) script.nonce = nonce

        script.onerror = () => {
            delete win[GLOBAL_KEY]
            delete win[CALLBACK_KEY]
            script.remove()
            reject(new Error('[filament-zone-map] Failed to load the Google Maps JavaScript API.'))
        }

        doc.head.append(script)
    })

    return win[GLOBAL_KEY]
}

/**
 * @param {{ key: string, language?: string|null, region?: string|null, version?: string }} options
 */
export function buildUrl({ key, language, region, version = 'weekly' }) {
    const params = new URLSearchParams({ key, v: version, loading: 'async', callback: CALLBACK_KEY })

    if (language) params.set('language', language)
    if (region) params.set('region', region)

    return `${API_URL}?${params.toString()}`
}
