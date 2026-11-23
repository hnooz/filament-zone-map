/**
 * Read-only Alpine component shared by ZoneMapEntry and ZonesMapWidget.
 * The session lives in a closure (Alpine proxies break Google Maps objects).
 */
import { ZoneViewerSession } from '../lib/viewer.js'

export default function zoneViewerComponent({ groups, config, labels }) {
    let session = null
    let resizeObserver = null

    return {
        error: null,

        init() {
            if (!config.key) {
                this.error = labels.missingKey
                return
            }

            session = new ZoneViewerSession(this.$refs.map, config, labels, {
                onError: (error) => {
                    console.error('[filament-zone-map]', error)
                    this.error = labels.loadFailed
                },
            })

            session.mount(groups)

            resizeObserver = new ResizeObserver(() => session?.refresh())
            resizeObserver.observe(this.$refs.map)
        },

        destroy() {
            resizeObserver?.disconnect()
            session?.destroy()
            session = null
        },
    }
}
