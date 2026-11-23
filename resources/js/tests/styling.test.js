import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import { hasStyleConflict, isDarkMode, resolveMapStyle } from '../lib/styling.js'

const light = [{ elementType: 'geometry', stylers: [{ color: '#ffffff' }] }]
const dark = [{ elementType: 'geometry', stylers: [{ color: '#000000' }] }]

describe('resolveMapStyle', () => {
    it('uses mapId with a color scheme and ignores styles', () => {
        const config = { mapId: 'abc', styles: { light, dark } }
        assert.deepEqual(resolveMapStyle(config, true), { mapId: 'abc', colorScheme: 'DARK' })
        assert.deepEqual(resolveMapStyle(config, false), { mapId: 'abc', colorScheme: 'LIGHT' })
    })

    it('reads the color scheme enum from google.maps when available', () => {
        const lib = { ColorScheme: { DARK: 'gm-dark', LIGHT: 'gm-light' } }
        assert.equal(resolveMapStyle({ mapId: 'abc' }, true, lib).colorScheme, 'gm-dark')
    })

    it('switches JSON styles by theme and falls back to light', () => {
        assert.deepEqual(resolveMapStyle({ styles: { light, dark } }, true), { styles: dark })
        assert.deepEqual(resolveMapStyle({ styles: { light, dark } }, false), { styles: light })
        assert.deepEqual(resolveMapStyle({ styles: { light } }, true), { styles: light })
        assert.deepEqual(resolveMapStyle({}, true), { styles: null })
    })
})

describe('hasStyleConflict', () => {
    it('flags mapId combined with styles', () => {
        assert.equal(hasStyleConflict({ mapId: 'abc', styles: { light } }), true)
        assert.equal(hasStyleConflict({ mapId: 'abc', styles: { light: null, dark: null } }), false)
        assert.equal(hasStyleConflict({ styles: { light } }), false)
    })
})

describe('isDarkMode', () => {
    it('reads the dark class on <html>', () => {
        const doc = (classes) => ({ documentElement: { classList: { contains: (c) => classes.includes(c) } } })
        assert.equal(isDarkMode(doc(['dark'])), true)
        assert.equal(isDarkMode(doc([])), false)
    })
})
