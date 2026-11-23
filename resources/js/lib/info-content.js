/**
 * Info window content for read-only maps. Built with DOM APIs and textContent
 * only, so zone names and record labels can never inject HTML.
 */

/** Relative paths and http(s) URLs only. Mirrors MapSettings::safeUrl() in PHP. */
export function safeUrl(url) {
    if (typeof url !== 'string' || url === '') return null
    if (url.startsWith('/') && !url.startsWith('//')) return url

    return /^https?:\/\//i.test(url) ? url : null
}

/**
 * @param {Document} doc
 * @param {{ name?: string|null, groupLabel?: string|null, url?: string|null, color?: string|null }} zone
 * @param {{ untitled: string, open: string }} labels
 * @returns {HTMLElement}
 */
export function buildInfoContent(doc, zone, labels) {
    const root = doc.createElement('div')
    root.className = 'fi-zone-map-info'

    const title = doc.createElement('p')
    title.className = 'fi-zone-map-info-title'

    if (zone.color) {
        const dot = doc.createElement('span')
        dot.className = 'fi-zone-map-info-dot'
        dot.style.backgroundColor = zone.color
        title.append(dot)
    }

    title.append(doc.createTextNode(zone.name || labels.untitled))
    root.append(title)

    if (zone.groupLabel) {
        const group = doc.createElement('p')
        group.className = 'fi-zone-map-info-group'
        group.textContent = zone.groupLabel
        root.append(group)
    }

    const href = safeUrl(zone.url)

    if (href) {
        const link = doc.createElement('a')
        link.className = 'fi-zone-map-info-link'
        link.href = href
        link.textContent = labels.open
        root.append(link)
    }

    return root
}
