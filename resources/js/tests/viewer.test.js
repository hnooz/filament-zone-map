import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import { buildInfoContent, safeUrl } from '../lib/info-content.js'
import { flattenGroups } from '../lib/viewer.js'

const square = [[[46.67, 24.71], [46.7, 24.71], [46.7, 24.74], [46.67, 24.71]]]
const zone = (id, properties = {}) => ({ type: 'Feature', id, geometry: { type: 'Polygon', coordinates: square }, properties })

/** Tiny DOM double: enough for createElement / append / textContent. */
function fakeDocument() {
    const element = (tag) => ({
        tag,
        children: [],
        style: {},
        className: '',
        textContent: '',
        append(...nodes) {
            this.children.push(...nodes)
        },
    })
    return { createElement: element, createTextNode: (text) => ({ tag: '#text', textContent: text }) }
}

const flatText = (node) => (node.tag === '#text' ? node.textContent : node.textContent + (node.children ?? []).map(flatText).join(''))

describe('safeUrl', () => {
    it('keeps relative and http(s) urls only', () => {
        assert.equal(safeUrl('/zones/1'), '/zones/1')
        assert.equal(safeUrl('https://a.test'), 'https://a.test')
        for (const bad of ['javascript:alert(1)', 'data:text/html,x', '//evil.test', '', null, 42]) {
            assert.equal(safeUrl(bad), null)
        }
    })
})

describe('buildInfoContent', () => {
    const labels = { untitled: 'Untitled zone', open: 'Open' }

    it('renders name, group and link as text nodes', () => {
        const root = buildInfoContent(fakeDocument(), { name: '<b>North</b>', groupLabel: 'Branch A', url: '/zones/1', color: '#E74C3C' }, labels)
        const text = flatText(root)

        assert.ok(text.includes('<b>North</b>'), 'name is plain text, not HTML')
        assert.ok(text.includes('Branch A'))
        assert.equal(root.children.at(-1).href, '/zones/1')
    })

    it('falls back to the untitled label and omits unsafe links', () => {
        const root = buildInfoContent(fakeDocument(), { name: null, url: 'javascript:alert(1)' }, labels)

        assert.ok(flatText(root).includes('Untitled zone'))
        assert.equal(root.children.some((child) => child.tag === 'a'), false)
    })
})

describe('flattenGroups', () => {
    it('merges groups with unique ids and group info', () => {
        const result = flattenGroups([
            { key: '1', label: 'North', url: '/1', zones: { type: 'FeatureCollection', features: [zone('a', { name: 'Z' })] } },
            { key: '2', label: 'South', url: null, zones: { type: 'FeatureCollection', features: [zone('a')] } },
        ])

        assert.deepEqual(result.features.map((f) => f.id), ['1:a', '2:a'])
        assert.deepEqual(result.features[0].properties, { name: 'Z', groupLabel: 'North', url: '/1' })
        assert.equal(result.features[1].properties.groupLabel, 'South')
    })

    it('ignores invalid groups and non-polygon features', () => {
        const result = flattenGroups([
            { key: '1', zones: null },
            { key: '2', zones: { type: 'FeatureCollection', features: [{ type: 'Feature', geometry: { type: 'Point', coordinates: [0, 0] } }] } },
        ])

        assert.equal(result.features.length, 0)
        assert.deepEqual(flattenGroups(undefined).features, [])
    })
})
