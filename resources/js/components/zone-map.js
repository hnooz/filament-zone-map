/**
 * Alpine component for the ZoneMap form field.
 *
 * The session is kept in a closure, not on the Alpine data object: Alpine wraps
 * data in reactive proxies, which breaks Google Maps and Terra Draw objects.
 */
import { ZoneMapSession } from '../lib/session.js'

const MESSAGE_MS = 4000
const CONFIRM_MS = 3000

export default function zoneMapFormComponent({ state, config, labels }) {
    let session = null
    let resizeObserver = null
    let messageTimer = null
    let confirmTimer = null

    return {
        state,
        config,
        mode: null,
        selectedId: null,
        zoneName: '',
        zoneColor: null,
        message: null,
        error: null,
        isConfirmingClear: false,

        get hasZones() {
            return (this.state?.features?.length ?? 0) > 0
        },

        init() {
            if (!config.key) {
                this.error = labels.missingKey
                return
            }

            session = new ZoneMapSession(this.$refs.map, config, {
                onChange: (collection) => {
                    this.state = collection.features.length > 0 ? collection : null
                },
                onSelect: (id) => this.syncSelection(id),
                onModeChange: (mode) => {
                    this.mode = mode
                },
                onLimit: (type, max) => this.flash((type === 'max_zones' ? labels.maxZones : labels.maxVertices).replace(':max', max)),
                onError: (error) => {
                    console.error('[filament-zone-map]', error)
                    this.error = labels.loadFailed
                },
            })

            session.mount(this.state)

            this.$watch('state', (value) => session?.setState(value))

            // Tabs, modals and collapsible sections: refresh once the map becomes visible.
            resizeObserver = new ResizeObserver(() => session?.refresh())
            resizeObserver.observe(this.$refs.map)
        },

        setMode(mode) {
            session?.setMode(mode)
        },

        syncSelection(id) {
            this.selectedId = id
            if (!id || !session) return

            const zone = session.currentState().features.find((feature) => feature.id === id)
            this.zoneName = zone?.properties?.name ?? ''
            this.zoneColor = zone?.properties?.color ?? config.zoneColor
        },

        updateName() {
            if (!this.selectedId) return

            const name = this.zoneName.trim().slice(0, config.maxNameLength ?? undefined)
            session?.updateZone(this.selectedId, { name: name === '' ? null : name })
        },

        pickColor(color) {
            if (!this.selectedId || !config.colors.includes(color)) return

            this.zoneColor = color
            session?.updateZone(this.selectedId, { color })
        },

        deleteSelected() {
            session?.deleteSelected()
        },

        clearAll() {
            if (!this.isConfirmingClear) {
                this.isConfirmingClear = true
                clearTimeout(confirmTimer)
                confirmTimer = setTimeout(() => {
                    this.isConfirmingClear = false
                }, CONFIRM_MS)
                return
            }

            clearTimeout(confirmTimer)
            this.isConfirmingClear = false
            session?.clear()
        },

        flash(text) {
            this.message = text
            clearTimeout(messageTimer)
            messageTimer = setTimeout(() => {
                this.message = null
            }, MESSAGE_MS)
        },

        destroy() {
            clearTimeout(messageTimer)
            clearTimeout(confirmTimer)
            resizeObserver?.disconnect()
            session?.destroy()
            session = null
        },
    }
}
