/**
 * Leaflet adapter for geometry-map.
 *
 * Assumes window.L and (for drawing types) window.L.Control.Draw
 * are present before mount() is called.
 */

export default class LeafletMapAdapter {
    constructor({ element, options, type, disabled = false }) {
        this.element = element
        this.options = options ?? {}
        this.type = type
        this.disabled = disabled

        this.map = null
        this.layers = null
        this.tiles = null
        this.drawControl = null
        this.scaleControl = null

        this.geometry = null

        this.changeListeners = []
        this.busyListeners = []

        this.listeners = []
        this.destroyed = false
    }

    async mount() {
        const L = window.L

        if (!L) {
            throw new Error(
                'Leaflet must be loaded before mounting the adapter.',
            )
        }

        const center =
            Array.isArray(this.options.center) &&
                this.options.center.length === 2
                ? this.options.center
                : [0, 0]

        const zoom = Number.isFinite(this.options.zoom)
            ? this.options.zoom
            : 2

        this.map = L.map(this.element).setView(center, zoom)

        if (this.options.tiles?.url) {
            this.tiles = L.tileLayer(this.options.tiles.url, {
                maxZoom: this.options.tiles.maxZoom,
                maxNativeZoom:
                    this.options.tiles.maxNativeZoom ??
                    this.options.tiles.maxZoom,
                attribution: this.options.tiles.attribution,
            }).addTo(this.map)
        }

        this.layers = new L.FeatureGroup().addTo(this.map)

        this.scaleControl = L.control.scale({ imperial: false })
        this.scaleControl.addTo(this.map)

        if (!this.disabled) {
            if (this.type === 'point') {
                this.configurePointEditing()
            } else {
                this.configureDrawing()
            }
        }

        this.applyTheme()

        // Leaflet needs a frame to measure the container after layout.
        window.requestAnimationFrame(() => {
            this.map?.invalidateSize()
        })

        return this
    }

    /** Register a Leaflet listener and track it for cleanup. */
    on(target, event, handler) {
        target.on(event, handler)
        this.listeners.push(() => target.off(event, handler))
    }

    setGeometry(geometry, { fit = false } = {}) {
        if (this.destroyed || !this.layers) {
            return
        }

        this.geometry = this.normalizeGeometry(geometry)
        this.layers.clearLayers()

        if (this.geometry) {
            const L = window.L

            L.geoJSON(this.geometry, {
                pointToLayer: (_, latlng) => this.createMarker(latlng),
            }).eachLayer((layer) => this.layers.addLayer(layer))
        }

        if (fit) {
            this.fitGeometry()
        }
    }

    getGeometry() {
        const layer = this.layers?.getLayers()?.[0]

        if (!layer) {
            return null
        }

        const precision = this.options.precision ?? 7

        return this.normalizeGeometry(
            layer.toGeoJSON(precision).geometry,
        )
    }

    /** Accept raw geometry, Feature, or FeatureCollection. */
    normalizeGeometry(geometry) {
        if (!geometry) {
            return null
        }

        if (geometry.type === 'Feature') {
            return geometry.geometry ?? null
        }

        if (geometry.type === 'FeatureCollection') {
            return geometry.features?.[0]?.geometry ?? null
        }

        if (!geometry.type || !geometry.coordinates) {
            return null
        }

        return {
            type: geometry.type,
            coordinates: geometry.coordinates,
        }
    }

    fitGeometry() {
        if (!this.map || !this.layers) {
            return
        }

        const layers = this.layers.getLayers()

        if (layers.length === 0) {
            return
        }

        const L = window.L

        // A single point: pan instead of fitting bounds.
        if (
            this.type === 'point' ||
            (layers.length === 1 && layers[0] instanceof L.Marker)
        ) {
            this.map.panTo(layers[0].getLatLng())
            return
        }

        const bounds = this.layers.getBounds()

        if (bounds.isValid()) {
            this.map.fitBounds(bounds, {
                maxZoom: this.options.fitMaxZoom ?? 18,
                padding: this.options.fitPadding ?? [30, 30],
            })
        }
    }

    configurePointEditing() {
        this.on(this.map, 'click', (event) => {
            this.layers.clearLayers()
            this.layers.addLayer(this.createMarker(event.latlng))
            this.emitChange(this.getGeometry())
        })
    }

    configureDrawing() {
        const L = window.L

        this.drawControl = new L.Control.Draw({
            position: 'topright',

            draw: {
                marker: false,

                polyline:
                    this.type === 'linestring'
                        ? { showLength: true, metric: true }
                        : false,

                polygon:
                    this.type === 'polygon'
                        ? { allowIntersection: false, showArea: false }
                        : false,

                rectangle: false,
                circle: false,
                circlemarker: false,
            },

            edit: {
                featureGroup: this.layers,
            },
        })

        this.map.addControl(this.drawControl)

        this.on(this.map, L.Draw.Event.DRAWSTART, () =>
            this.emitBusy(true),
        )
        this.on(this.map, L.Draw.Event.DRAWSTOP, () =>
            this.emitBusy(false),
        )
        this.on(this.map, L.Draw.Event.EDITSTART, () =>
            this.emitBusy(true),
        )
        this.on(this.map, L.Draw.Event.EDITSTOP, () =>
            this.emitBusy(false),
        )

        this.on(this.map, L.Draw.Event.CREATED, (event) => {
            this.layers.clearLayers()
            this.layers.addLayer(event.layer)
            this.emitChange(this.getGeometry())
        })

        this.on(this.map, L.Draw.Event.EDITED, () => {
            this.emitChange(this.getGeometry())
        })

        this.on(this.map, L.Draw.Event.DELETED, () => {
            this.emitChange(null)
        })
    }

    createMarker(latlng) {
        const L = window.L

        const marker = L.marker(latlng, {
            draggable: !this.disabled,
        })

        if (!this.disabled) {
            // Not tracked via this.on(): the marker is discarded with the
            // feature group and its listeners are collected with it.
            marker.on('dragend', () => {
                this.emitChange(this.getGeometry())
            })
        }

        return marker
    }

    onChange(callback) {
        this.changeListeners.push(callback)

        return () => {
            this.changeListeners = this.changeListeners.filter(
                (listener) => listener !== callback,
            )
        }
    }

    onBusyChange(callback) {
        this.busyListeners.push(callback)

        return () => {
            this.busyListeners = this.busyListeners.filter(
                (listener) => listener !== callback,
            )
        }
    }

    emitChange(geometry) {
        this.geometry = this.normalizeGeometry(geometry)

        this.changeListeners.forEach((listener) => listener(this.geometry))
    }

    emitBusy(value) {
        this.busyListeners.forEach((listener) => listener(value))
    }

    resize() {
        this.map?.invalidateSize()
    }

    applyTheme() {
        const dark = document.documentElement.classList.contains('dark')

        if (this.tiles) {
            this.tiles.getContainer().style.filter = dark
                ? 'invert(1) hue-rotate(180deg) brightness(0.95) contrast(0.9)'
                : ''
        }

        if (this.layers) {
            const stroke = dark ? '#f5f5f5' : '#2563eb'

            this.layers.eachLayer((layer) => {
                if (layer instanceof window.L.Marker) {
                    return
                }

                if (typeof layer.setStyle === 'function') {
                    layer.setStyle({ color: stroke })
                }
            })
        }
    }

    destroy() {
        if (this.destroyed) {
            return
        }

        this.destroyed = true
        this.changeListeners = []
        this.busyListeners = []

        this.listeners.forEach((cleanup) => {
            try {
                cleanup()
            } catch {
                // Ignore cleanup errors.
            }
        })
        this.listeners = []

        if (this.map) {
            if (this.drawControl) {
                this.map.removeControl(this.drawControl)
            }

            if (this.scaleControl) {
                this.map.removeControl(this.scaleControl)
            }

            this.map.remove()
        }

        this.map = null
        this.layers = null
        this.tiles = null
        this.drawControl = null
        this.scaleControl = null
    }
}