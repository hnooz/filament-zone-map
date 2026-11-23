/**
 * Map styling: cloud styling (mapId) first, JSON styles as fallback,
 * light/dark following Filament's theme (`.dark` on <html>).
 */

/**
 * Google ignores `styles` when a `mapId` is set.
 *
 * @param {{ mapId?: string|null, styles?: { light?: unknown[]|null, dark?: unknown[]|null } }} config
 */
export function hasStyleConflict(config) {
    return Boolean(config.mapId && (config.styles?.light || config.styles?.dark))
}

/**
 * Map options for the current theme.
 * `colorScheme` is read only when the map is created, so a theme change
 * with a mapId means rebuilding the map. `styles` can be changed live.
 *
 * @param {{ mapId?: string|null, styles?: { light?: unknown[]|null, dark?: unknown[]|null } }} config
 * @param {boolean} isDark
 * @param {{ ColorScheme?: Record<string, string> }} [lib] google.maps namespace
 * @returns {{ mapId: string, colorScheme: string } | { styles: unknown[]|null }}
 */
export function resolveMapStyle(config, isDark, lib) {
    if (config.mapId) {
        const scheme = isDark ? 'DARK' : 'LIGHT'

        return { mapId: config.mapId, colorScheme: lib?.ColorScheme?.[scheme] ?? scheme }
    }

    const light = config.styles?.light ?? null
    const dark = config.styles?.dark ?? null

    return { styles: isDark ? (dark ?? light) : light }
}

/**
 * @param {Document} [doc]
 */
export function isDarkMode(doc = document) {
    return doc.documentElement.classList.contains('dark')
}

/**
 * Calls `onChange(isDark)` when Filament switches theme. Returns a stop function.
 *
 * @param {(isDark: boolean) => void} onChange
 * @param {Document} [doc]
 * @returns {() => void}
 */
export function watchTheme(onChange, doc = document) {
    let current = isDarkMode(doc)

    const observer = new MutationObserver(() => {
        const next = isDarkMode(doc)

        if (next !== current) {
            current = next
            onChange(next)
        }
    })

    observer.observe(doc.documentElement, { attributes: true, attributeFilter: ['class'] })

    return () => observer.disconnect()
}
