export default class LeafletMapAdapter {
    constructor({
        element,
        options,
        type,
        disabled = false,
    }) {
        this.element = element
        this.options = options
        this.type = type
        this.disabled = disabled

        this.map = null
        this.layers = null
        this.tiles = null
        this.drawControl = null

        this.geometry = null

        this.changeListeners = []
        this.busyListeners = []
    }

    async mount() {
        const L = window.L

        this.map = L.map(this.element).setView(
            this.options.center,
            this.options.zoom,
        )

        this.tiles = L.tileLayer(
            this.options.tiles.url,
            {
                maxZoom: this.options.tiles.maxZoom,
                attribution: this.options.tiles.attribution,
            },
        ).addTo(this.map)

        this.layers = new L.FeatureGroup().addTo(this.map)

        if (!this.disabled && this.type !== 'point') {
            this.configureDrawing()
        }

        if (!this.disabled && this.type === 'point') {
            this.configurePointEditing()
        }

        this.applyTheme()

        return this
    }

    setGeometry(geometry, { fit = false } = {}) {
        this.geometry = geometry ?? null

        this.layers.clearLayers()

        if (!this.geometry) {
            return
        }

        const L = window.L

        L.geoJSON(this.geometry, {
            pointToLayer: (_, latlng) =>
                this.createMarker(latlng),
        }).eachLayer((layer) => {
            this.layers.addLayer(layer)
        })

        if (fit) {
            this.fitGeometry()
        }
    }

    getGeometry() {
        const layer = this.layers?.getLayers()[0]

        if (!layer) {
            return null
        }

        return layer.toGeoJSON(7).geometry
    }

    clear() {
        this.geometry = null
        this.layers?.clearLayers()
        this.emitChange(null)
    }

    fitGeometry() {
        if (!this.map || !this.layers) {
            return
        }

        if (this.type === 'point') {
            const layer = this.layers.getLayers()[0]

            if (layer) {
                this.map.panTo(layer.getLatLng())
            }

            return
        }

        const bounds = this.layers.getBounds()

        if (bounds.isValid()) {
            this.map.fitBounds(
                bounds,
                {
                    maxZoom: 18,
                    padding: [30, 30],
                },
            )
        }
    }

    startDrawing() {
        const toolbar = this.drawControl?._toolbars?.draw

        if (!toolbar) {
            return
        }

        const mode = this.type === 'linestring'
            ? 'polyline'
            : 'polygon'

        toolbar._modes?.[mode]?.handler?.enable()
    }

    finishDrawing() {
        const toolbar = this.drawControl?._toolbars?.draw

        if (!toolbar) {
            return
        }

        const mode = this.type === 'linestring'
            ? 'polyline'
            : 'polygon'

        toolbar._modes?.[mode]?.handler?.completeShape?.()
    }

    onChange(callback) {
        this.changeListeners.push(callback)

        return () => {
            this.changeListeners =
                this.changeListeners.filter(
                    (listener) => listener !== callback,
                )
        }
    }

    onBusyChange(callback) {
        this.busyListeners.push(callback)

        return () => {
            this.busyListeners =
                this.busyListeners.filter(
                    (listener) => listener !== callback,
                )
        }
    }

    resize() {
        this.map?.invalidateSize()
    }

    destroy() {
        this.changeListeners = []
        this.busyListeners = []

        if (this.map) {
            this.map.off()

            if (this.drawControl) {
                this.map.removeControl(
                    this.drawControl,
                )
            }

            this.map.remove()
        }

        this.map = null
        this.layers = null
        this.tiles = null
        this.drawControl = null
    }

    configurePointEditing() {
        this.map.on('click', (event) => {
            this.layers.clearLayers()

            this.layers.addLayer(
                this.createMarker(event.latlng),
            )

            this.emitChange(
                this.getGeometry(),
            )
        })
    }

    configureDrawing() {
        const L = window.L

        this.drawControl = new L.Control.Draw({
            position: 'topright',

            draw: {
                marker: false,

                polyline: this.type === 'linestring'
                    ? {
                        showLength: true,
                        metric: true,
                    }
                    : false,

                polygon: this.type === 'polygon'
                    ? {
                        allowIntersection: false,
                        showArea: false,
                    }
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

        this.map.on(
            L.Draw.Event.DRAWSTART,
            () => this.emitBusy(true),
        )

        this.map.on(
            L.Draw.Event.DRAWSTOP,
            () => this.emitBusy(false),
        )

        this.map.on(
            L.Draw.Event.EDITSTART,
            () => this.emitBusy(true),
        )

        this.map.on(
            L.Draw.Event.EDITSTOP,
            () => this.emitBusy(false),
        )

        this.map.on(
            L.Draw.Event.CREATED,
            (event) => {
                this.layers.clearLayers()
                this.layers.addLayer(event.layer)

                this.emitChange(
                    this.getGeometry(),
                )
            },
        )

        this.map.on(
            L.Draw.Event.EDITED,
            () => {
                this.emitChange(
                    this.getGeometry(),
                )
            },
        )

        this.map.on(
            L.Draw.Event.DELETED,
            () => {
                this.emitChange(null)
            },
        )
    }

    createMarker(latlng) {
        const L = window.L

        const marker = L.marker(
            latlng,
            {
                draggable: !this.disabled,
            },
        )

        if (!this.disabled) {
            marker.on(
                'dragend',
                () => {
                    this.emitChange(
                        this.getGeometry(),
                    )
                },
            )
        }

        return marker
    }

    emitChange(geometry) {
        this.geometry = geometry ?? null

        this.changeListeners.forEach(
            (listener) => listener(this.geometry),
        )
    }

    emitBusy(value) {
        this.busyListeners.forEach(
            (listener) => listener(value),
        )
    }

    applyTheme() {
        if (!this.tiles) {
            return
        }

        const dark =
            document.documentElement.classList.contains('dark')

        this.tiles.getContainer().style.filter = dark
            ? 'invert(1) hue-rotate(180deg) brightness(0.95) contrast(0.9)'
            : ''
    }
}