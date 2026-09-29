const CDN = 'https://cdnjs.cloudflare.com/ajax/libs'
let leafletPromise = null

const loadStyle = (href) =>
    new Promise((resolve, reject) => {
        if (document.querySelector(`link[href="${href}"]`)) return resolve()
        const link = Object.assign(document.createElement('link'), { rel: 'stylesheet', href })
        link.onload = resolve
        link.onerror = reject
        document.head.appendChild(link)
    })

const loadScript = (src) =>
    new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${src}"]`)) return resolve()
        const script = Object.assign(document.createElement('script'), { src })
        script.onload = resolve
        script.onerror = reject
        document.head.appendChild(script)
    })

// Leaflet.draw 1.0.4 can fire mouse handlers after a tool is disabled,
// when its tooltip has already been destroyed. Ignore those late events.
const patchLeafletDraw = () => {
    const L = window.L
    if (L.Draw.__guarded) return
    L.Draw.__guarded = true

    const guard = (proto, method) => {
        const original = proto?.[method]
        if (!original) return

        proto[method] = function (...args) {
            return this._tooltip ? original.apply(this, args) : undefined
        }
    }

    guard(L.Draw.Polyline.prototype, '_onMouseMove')
    guard(L.Draw.Polyline.prototype, '_updateTooltip')
    guard(L.Draw.Marker.prototype, '_onMouseMove')
    guard(L.Draw.Feature.prototype, '_updateTooltip')
}

// Shared across every picker on the page; retried if it fails.
const loadLeaflet = () =>
(leafletPromise ??= Promise.all([
    loadStyle(`${CDN}/leaflet/1.9.4/leaflet.min.css`),
    loadStyle(`${CDN}/leaflet.draw/1.0.4/leaflet.draw.css`),
    loadScript(`${CDN}/leaflet/1.9.4/leaflet.min.js`)
        .then(() => loadScript(`${CDN}/leaflet.draw/1.0.4/leaflet.draw.js`))
        .then(patchLeafletDraw),
]).catch((error) => {
    leafletPromise = null
    throw error
}))

export default function geometryPicker({ state, type, center, zoom, disabled }) {
    return {
        state,
        map: null,
        layers: null,
        tiles: null,
        drawControl: null,
        drawing: null, // active draw tool (e.g. 'polyline'), or null
        busy: false, // true while the user is drawing or editing
        observers: [],
        cleanups: [],

        async init() {
            await loadLeaflet()
            const L = window.L

            this.map = L.map(this.$refs.map).setView(center, zoom)
            this.tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(this.map)
            this.layers = new L.FeatureGroup().addTo(this.map)

            this.render(this.state)

            if (!disabled) {
                this.addDrawControls()
                this.hookFormSubmit()
            }

            // Re-render only when the change came from outside the map
            // (form reset, other fields...), and never mid-draw or mid-edit.
            this.$watch('state', (value) => {
                if (this.busy) return

                if (JSON.stringify(value ?? null) !== JSON.stringify(this.current())) {
                    this.render(value)
                }
            })

            this.watchResize()
            this.watchTheme()
        },

        addDrawControls() {
            const L = window.L

            this.drawControl = new L.Control.Draw({
                position: 'topright',
                draw: {
                    marker: type === 'point',
                    polyline: type === 'linestring' ? { showLength: true, metric: true } : false,
                    polygon: type === 'polygon' ? { allowIntersection: false, showArea: false } : false,
                    rectangle: false,
                    circle: false,
                    circlemarker: false,
                },
                edit: { featureGroup: this.layers },
            })
            this.map.addControl(this.drawControl)

            this.map.on(L.Draw.Event.DRAWSTART, (event) => {
                this.drawing = event.layerType
                this.busy = true
            })
            this.map.on(L.Draw.Event.DRAWSTOP, () => {
                this.drawing = null
                this.busy = false
            })
            this.map.on(L.Draw.Event.EDITSTART, () => {
                this.busy = true
            })
            this.map.on(L.Draw.Event.EDITSTOP, () => {
                this.busy = false
            })

            this.map.on(L.Draw.Event.CREATED, (event) => {
                this.layers.clearLayers() // one geometry per field
                this.layers.addLayer(event.layer)
                this.commit()
            })
            this.map.on(L.Draw.Event.EDITED, () => this.commit())
            this.map.on(L.Draw.Event.DELETED, () => this.commit())
        },

        // Complete an in-progress line when the form is submitted. Capture phase,
        // so it runs before Livewire reads the state.
        hookFormSubmit() {
            const form = this.$root.closest('form')
            if (!form) return

            const onSubmit = () => this.finishDrawing()
            form.addEventListener('submit', onSubmit, true)
            this.cleanups.push(() => form.removeEventListener('submit', onSubmit, true))
        },

        // completeShape() does nothing unless the shape is valid (2+ points for a line),
        // in which case server-side validation reports the problem.
        finishDrawing() {
            if (!this.drawing) return

            const handler = this.drawControl?._toolbars?.draw?._modes?.[this.drawing]?.handler
            handler?.completeShape?.()
        },

        render(value) {
            this.layers.clearLayers()
            if (!value?.type) return

            window.L.geoJSON(value).eachLayer((layer) => this.layers.addLayer(layer))

            const bounds = this.layers.getBounds()
            if (bounds.isValid()) this.map.fitBounds(bounds, { maxZoom: 18, padding: [30, 30] })
        },

        current() {
            const layer = this.layers?.getLayers()[0]
            return layer ? layer.toGeoJSON(7).geometry : null
        },

        commit() {
            this.state = this.current()
        },

        // Fixes a blank or misaligned map inside tabs and collapsed sections.
        watchResize() {
            const observer = new ResizeObserver(() => this.map?.invalidateSize())
            observer.observe(this.$refs.map)
            this.observers.push(observer)
        },

        watchTheme() {
            const apply = () => {
                const dark = document.documentElement.classList.contains('dark')
                this.tiles.getContainer().style.filter = dark
                    ? 'invert(1) hue-rotate(180deg) brightness(0.95) contrast(0.9)'
                    : ''
            }
            apply()

            const observer = new MutationObserver(apply)
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
            this.observers.push(observer)
        },

        destroy() {
            this.cleanups.forEach((cleanup) => cleanup())
            this.observers.forEach((observer) => observer.disconnect())

            if (this.map) {
                this.map.off()
                if (this.drawControl) this.map.removeControl(this.drawControl)
                this.map.remove()
            }

            this.map = null
            this.drawControl = null
            this.layers = null
            this.tiles = null
        },
    }
}