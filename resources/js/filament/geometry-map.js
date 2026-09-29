import LeafletMapAdapter from './maps/leaflet-adapter'

const LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
const LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'

const LEAFLET_DRAW_CSS =
    'https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css'

const LEAFLET_DRAW_JS =
    'https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js'

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
                        element: this.$refs.map,
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
                this.hookFormSubmit()
            } catch (error) {
                console.error(
                    'Unable to initialize geometry map.',
                    error,
                )
            }
        },

        value() {
            if (bound) {
                const lat = Number.parseFloat(
                    this.latitude,
                )

                const lng = Number.parseFloat(
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
                ] = geometry?.coordinates ?? [
                    null,
                    null,
                ]

                this.latitude = latitude
                this.longitude = longitude

                return
            }

            this.state = geometry
        },

        attachAdapterListeners() {
            const removeChangeListener =
                this.adapter.onChange(
                    (geometry) => {
                        this.write(geometry)
                    },
                )

            const removeBusyListener =
                this.adapter.onBusyChange(
                    (busy) => {
                        this.busy = busy
                    },
                )

            this.cleanups.push(
                removeChangeListener,
                removeBusyListener,
            )
        },

        attachStateWatchers() {
            if (bound) {
                this.$watch(
                    'latitude',
                    () => this.syncExternalState(),
                )

                this.$watch(
                    'longitude',
                    () => this.syncExternalState(),
                )

                return
            }

            this.$watch(
                'state',
                () => this.syncExternalState(),
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

        hookFormSubmit() {
            const form =
                this.$root.closest('form')

            if (!form) {
                return
            }

            const submit = () => {
                if (!this.busy) {
                    return
                }

                this.adapter?.finishDrawing()
            }

            form.addEventListener(
                'submit',
                submit,
                true,
            )

            this.cleanups.push(
                () => form.removeEventListener(
                    'submit',
                    submit,
                    true,
                ),
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
                new ResizeObserver(() => {
                    this.adapter?.resize()
                })

            observer.observe(
                this.$refs.map,
            )

            this.observers.push(observer)
        },

        watchTheme() {
            const apply =
                () => this.adapter?.applyTheme()

            apply()

            const observer =
                new MutationObserver(apply)

            observer.observe(
                document.documentElement,
                {
                    attributes: true,
                    attributeFilter: [
                        'class',
                    ],
                },
            )

            this.observers.push(observer)
        },

        destroy() {
            this.cleanups.forEach(
                (cleanup) => cleanup(),
            )

            this.observers.forEach(
                (observer) => observer.disconnect(),
            )

            this.adapter?.destroy()

            this.adapter = null
        },
    }
}