const LEAFLET_CSS =
    'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css'

const LEAFLET_JS =
    'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js'

const LEAFLET_DRAW_CSS =
    'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css'

const LEAFLET_DRAW_JS =
    'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js'


/*
|--------------------------------------------------------------------------
| External asset loading
|--------------------------------------------------------------------------
*/

function loadCss(href) {
    return new Promise((resolve, reject) => {
        if (
            document.querySelector(
                `link[data-parking-map="${href}"]`,
            )
        ) {
            resolve()

            return
        }

        const link = document.createElement('link')

        link.rel = 'stylesheet'
        link.href = href
        link.dataset.parkingMap = href

        link.onload = resolve
        link.onerror = reject

        document.head.appendChild(link)
    })
}


function loadScript(src) {
    return new Promise((resolve, reject) => {
        if (
            document.querySelector(
                `script[data-parking-map="${src}"]`,
            )
        ) {
            resolve()

            return
        }

        const script = document.createElement('script')

        script.src = src
        script.dataset.parkingMap = src

        script.onload = resolve
        script.onerror = reject

        document.head.appendChild(script)
    })
}


let leafletPromise = null
let leafletDrawPromise = null


function loadLeaflet() {
    if (leafletPromise) {
        return leafletPromise
    }

    leafletPromise = Promise.all([
        loadCss(LEAFLET_CSS),
        loadScript(LEAFLET_JS),
    ]).catch((error) => {
        leafletPromise = null

        throw error
    })

    return leafletPromise
}


function loadLeafletDraw() {
    if (leafletDrawPromise) {
        return leafletDrawPromise
    }

    leafletDrawPromise = loadLeaflet()
        .then(() =>
            Promise.all([
                loadCss(LEAFLET_DRAW_CSS),
                loadScript(LEAFLET_DRAW_JS),
            ]),
        )
        .catch((error) => {
            leafletDrawPromise = null

            throw error
        })

    return leafletDrawPromise
}


/*
|--------------------------------------------------------------------------
| Leaflet adapter
|--------------------------------------------------------------------------
|
| Everything Leaflet-specific stays here.
|
*/

class LeafletMapAdapter {
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

        this.layers =
            new L.FeatureGroup().addTo(this.map)

        if (
            !this.disabled &&
            this.type !== 'point'
        ) {
            this.configureDrawing()
        }

        if (
            !this.disabled &&
            this.type === 'point'
        ) {
            this.configurePointEditing()
        }

        this.applyTheme()

        return this
    }

    setGeometry(
        geometry,
        { fit = false } = {},
    ) {
        this.geometry =
            geometry ?? null

        this.layers.clearLayers()

        if (!this.geometry) {
            return
        }

        const L = window.L

        L.geoJSON(
            this.geometry,
            {
                pointToLayer: (_, latlng) =>
                    this.createMarker(latlng),
            },
        ).eachLayer(
            (layer) =>
                this.layers.addLayer(layer),
        )

        if (fit) {
            this.fitGeometry()
        }
    }

    getGeometry() {
        const layer =
            this.layers?.getLayers()[0]

        if (!layer) {
            return null
        }

        return layer.toGeoJSON(7).geometry
    }

    fitGeometry() {
        if (!this.map || !this.layers) {
            return
        }

        if (this.type === 'point') {
            const layer =
                this.layers.getLayers()[0]

            if (layer) {
                this.map.panTo(
                    layer.getLatLng(),
                )
            }

            return
        }

        const bounds =
            this.layers.getBounds()

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

    configurePointEditing() {
        this.map.on(
            'click',
            (event) => {
                this.layers.clearLayers()

                this.layers.addLayer(
                    this.createMarker(
                        event.latlng,
                    ),
                )

                this.emitChange(
                    this.getGeometry(),
                )
            },
        )
    }

    configureDrawing() {
        const L = window.L

        this.drawControl =
            new L.Control.Draw({
                position: 'topright',

                draw: {
                    marker: false,

                    polyline:
                        this.type === 'linestring'
                            ? {
                                showLength: true,
                                metric: true,
                            }
                            : false,

                    polygon:
                        this.type === 'polygon'
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
                    featureGroup:
                        this.layers,
                },
            })

        this.map.addControl(
            this.drawControl,
        )

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

                this.layers.addLayer(
                    event.layer,
                )

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
                draggable:
                    !this.disabled,
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

    onChange(callback) {
        this.changeListeners.push(
            callback,
        )

        return () => {
            this.changeListeners =
                this.changeListeners.filter(
                    (listener) =>
                        listener !== callback,
                )
        }
    }

    onBusyChange(callback) {
        this.busyListeners.push(
            callback,
        )

        return () => {
            this.busyListeners =
                this.busyListeners.filter(
                    (listener) =>
                        listener !== callback,
                )
        }
    }

    emitChange(geometry) {
        this.geometry =
            geometry ?? null

        this.changeListeners.forEach(
            (listener) =>
                listener(this.geometry),
        )
    }

    emitBusy(value) {
        this.busyListeners.forEach(
            (listener) =>
                listener(value),
        )
    }

    resize() {
        this.map?.invalidateSize()
    }

    applyTheme() {
        if (!this.tiles) {
            return
        }

        const dark =
            document.documentElement.classList.contains(
                'dark',
            )

        this.tiles.getContainer().style.filter =
            dark
                ? 'invert(1) hue-rotate(180deg) brightness(0.95) contrast(0.9)'
                : ''
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
}


/*
|--------------------------------------------------------------------------
| Alpine component
|--------------------------------------------------------------------------
*/

export default function geometryMap({
    state,
    latitude,
    longitude,
    bound,
    type,
    map: options,
    disabled,
}) {
    return {
        state,
        latitude,
        longitude,

        adapter: null,

        busy: false,

        observers: [],

        cleanups: [],

        async init() {
            try {
                const requiresDrawing =
                    !disabled &&
                    type !== 'point'

                if (requiresDrawing) {
                    await loadLeafletDraw()
                } else {
                    await loadLeaflet()
                }

                this.adapter =
                    new LeafletMapAdapter({
                        element:
                            this.$refs.map,

                        options,

                        type,

                        disabled,
                    })

                await this.adapter.mount()

                this.adapter.setGeometry(
                    this.value(),
                    {
                        fit: true,
                    },
                )

                this.attachAdapterListeners()

                this.attachStateWatchers()

                this.watchResize()

                this.watchTheme()
            } catch (error) {
                console.error(
                    'Unable to initialize geometry map.',
                    error,
                )
            }
        },

        value() {
            if (bound) {
                const lat =
                    Number.parseFloat(
                        this.latitude,
                    )

                const lng =
                    Number.parseFloat(
                        this.longitude,
                    )

                if (
                    !Number.isFinite(lat) ||
                    !Number.isFinite(lng)
                ) {
                    return null
                }

                return {
                    type: 'Point',

                    coordinates: [
                        lng,
                        lat,
                    ],
                }
            }

            return this.state ?? null
        },

        write(geometry) {
            if (bound) {
                const [
                    longitude,
                    latitude,
                ] =
                    geometry?.coordinates ??
                    [null, null]

                this.latitude =
                    latitude

                this.longitude =
                    longitude

                return
            }

            this.state = geometry
        },

        attachAdapterListeners() {
            this.cleanups.push(
                this.adapter.onChange(
                    (geometry) => {
                        this.write(
                            geometry,
                        )
                    },
                ),
            )

            this.cleanups.push(
                this.adapter.onBusyChange(
                    (busy) => {
                        this.busy = busy
                    },
                ),
            )
        },

        attachStateWatchers() {
            if (bound) {
                this.$watch(
                    'latitude',
                    () =>
                        this.syncExternalState(),
                )

                this.$watch(
                    'longitude',
                    () =>
                        this.syncExternalState(),
                )

                return
            }

            this.$watch(
                'state',
                () =>
                    this.syncExternalState(),
            )
        },

        syncExternalState() {
            if (this.busy) {
                return
            }

            const external =
                this.value()

            const current =
                this.adapter?.getGeometry()

            if (
                JSON.stringify(external) ===
                JSON.stringify(current)
            ) {
                return
            }

            this.adapter?.setGeometry(
                external,
                {
                    fit: false,
                },
            )
        },

        watchResize() {
            if (
                typeof ResizeObserver ===
                'undefined'
            ) {
                return
            }

            const observer =
                new ResizeObserver(
                    () =>
                        this.adapter?.resize(),
                )

            observer.observe(
                this.$refs.map,
            )

            this.observers.push(
                observer,
            )
        },

        watchTheme() {
            const apply =
                () =>
                    this.adapter?.applyTheme()

            apply()

            const observer =
                new MutationObserver(
                    apply,
                )

            observer.observe(
                document.documentElement,
                {
                    attributes: true,

                    attributeFilter: [
                        'class',
                    ],
                },
            )

            this.observers.push(
                observer,
            )
        },

        destroy() {
            this.cleanups.forEach(
                (cleanup) =>
                    cleanup(),
            )

            this.observers.forEach(
                (observer) =>
                    observer.disconnect(),
            )

            this.adapter?.destroy()

            this.adapter = null
        },
    }
}