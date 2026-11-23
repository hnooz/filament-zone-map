import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import { buildUrl, loadGoogleMaps } from '../lib/loader.js'

/** Minimal window/document doubles: records appended scripts. */
function fakeEnv() {
    const scripts = []
    const doc = {
        createElement: () => ({ remove() {} }),
        querySelector: () => null,
        head: { append: (script) => scripts.push(script) },
    }
    return { win: {}, doc, scripts }
}

describe('loadGoogleMaps', () => {
    it('injects one script for concurrent calls and resolves via the callback', async () => {
        const { win, doc, scripts } = fakeEnv()

        const first = loadGoogleMaps({ key: 'k' }, { win, doc })
        const second = loadGoogleMaps({ key: 'k' }, { win, doc })

        assert.equal(first, second)
        assert.equal(scripts.length, 1)
        assert.equal(scripts[0].async, true)

        win.google = { maps: { importLibrary: () => {} } }
        win.__filamentZoneMapGoogleMapsReady()

        assert.equal(await first, win.google.maps)
        assert.equal(win.__filamentZoneMapGoogleMapsReady, undefined)
    })

    it('reuses an already loaded Google Maps', async () => {
        const { win, doc, scripts } = fakeEnv()
        win.google = { maps: { importLibrary: () => {} } }

        assert.equal(await loadGoogleMaps({ key: 'k' }, { win, doc }), win.google.maps)
        assert.equal(scripts.length, 0)
    })

    it('rejects without a key', async () => {
        const { win, doc } = fakeEnv()
        await assert.rejects(loadGoogleMaps({ key: null }, { win, doc }), /browser key is missing/)
    })

    it('allows a retry after a load error', async () => {
        const { win, doc, scripts } = fakeEnv()

        const failed = loadGoogleMaps({ key: 'k' }, { win, doc })
        scripts[0].onerror()
        await assert.rejects(failed, /Failed to load/)

        loadGoogleMaps({ key: 'k' }, { win, doc })
        assert.equal(scripts.length, 2)
    })

    it('copies the CSP nonce from the page', () => {
        const { win, doc, scripts } = fakeEnv()
        doc.querySelector = () => ({ nonce: 'abc' })

        loadGoogleMaps({ key: 'k' }, { win, doc })
        assert.equal(scripts[0].nonce, 'abc')
    })
})

describe('buildUrl', () => {
    it('uses async loading with callback, language and region', () => {
        const url = new URL(buildUrl({ key: 'k', language: 'ar', region: 'SA' }))

        assert.equal(url.origin + url.pathname, 'https://maps.googleapis.com/maps/api/js')
        assert.equal(url.searchParams.get('loading'), 'async')
        assert.equal(url.searchParams.get('callback'), '__filamentZoneMapGoogleMapsReady')
        assert.equal(url.searchParams.get('language'), 'ar')
        assert.equal(url.searchParams.get('region'), 'SA')
        assert.equal(url.searchParams.get('v'), 'weekly')
    })
})
