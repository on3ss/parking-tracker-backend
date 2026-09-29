import LeafletMapAdapter from './leaflet-adapter.js'

/* ------------------------------------------------------------------ */
/*  CDN assets                                                         */
/* ------------------------------------------------------------------ */

const LEAFLET_CSS =
    'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css'

const LEAFLET_JS =
    'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js'

const LEAFLET_DRAW_CSS =
    'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css'

const LEAFLET_DRAW_JS =
    'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js'

/* ------------------------------------------------------------------ */
/*  Asset loading                                                      */
/* ------------------------------------------------------------------ */

function loadCss(href) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(
            `link[data-geometry-map="${href}"]`,
        )

        if (existing) {
            if (existing.sheet) {
                resolve()
                return
            }

            existing.addEventListener('load', resolve, { once: true })
            existing.addEventListener('error', reject, { once: true })

            return
        }

        const link = document.createElement('link')
        link.rel = 'stylesheet'
        link.href = href
        link.dataset.geometryMap = href

        link.addEventListener('load', resolve, { once: true })
        link.addEventListener(
            'error',
            () => {
                link.remove()
                reject(new Error(`Failed to load stylesheet: ${href}`))
            },
            { once: true },
        )

        document.head.appendChild(link)
    })
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(
            `script[data-geometry-map="${src}"]`,
        )

        if (existing) {
            if (existing.dataset.loaded === 'true') {
                resolve()
                return
            }

            existing.addEventListener('load', resolve, { once: true })
            existing.addEventListener('error', reject, { once: true })

            return
        }

        const script = document.createElement('script')
        script.src = src
        script.dataset.geometryMap = src

        script.addEventListener(
            'load',
            () => {
                script.dataset.loaded = 'true'
                resolve()
            },
            { once: true },
        )

        script.addEventListener(
            'error',
            () => {
                script.remove()
                reject(new Error(`Failed to load script: ${src}`))
            },
            { once: true },
        )

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

/* ------------------------------------------------------------------ */
/*  Alpine component                                                   */
/* ------------------------------------------------------------------ */

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
                if (!disabled && type !== 'point') {
                    await loadLeafletDraw()
                } else {
                    await loadLeaflet()
                }

                this.adapter = new LeafletMapAdapter({
                    element: this.$refs.map,
                    options,
                    type,
                    disabled,
                })

                await this.adapter.mount()

                this.adapter.setGeometry(this.value(), { fit: true })

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
                const lat = Number.parseFloat(this.latitude)
                const lng = Number.parseFloat(this.longitude)

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    return null
                }

                return {
                    type: 'Point',
                    coordinates: [lng, lat],
                }
            }

            return this.state ?? null
        },

        write(geometry) {
            if (bound) {
                const [longitude, latitude] =
                    geometry?.coordinates ?? [null, null]

                this.latitude = latitude
                this.longitude = longitude

                return
            }

            this.state = geometry
        },

        attachAdapterListeners() {
            this.cleanups.push(
                this.adapter.onChange((geometry) => this.write(geometry)),
            )

            this.cleanups.push(
                this.adapter.onBusyChange((busy) => {
                    this.busy = busy
                }),
            )
        },

        attachStateWatchers() {
            if (bound) {
                this.$watch('latitude', () => this.syncExternalState())
                this.$watch('longitude', () => this.syncExternalState())
                return
            }

            this.$watch('state', () => this.syncExternalState())
        },

        syncExternalState() {
            if (this.busy) {
                return
            }

            const external = this.value()
            const current = this.adapter?.getGeometry()

            if (JSON.stringify(external) === JSON.stringify(current)) {
                return
            }

            this.adapter?.setGeometry(external, { fit: false })
        },

        watchResize() {
            if (typeof ResizeObserver === 'undefined') {
                return
            }

            const observer = new ResizeObserver(() =>
                this.adapter?.resize(),
            )

            observer.observe(this.$refs.map)
            this.observers.push(observer)
        },

        watchTheme() {
            const apply = () => this.adapter?.applyTheme()

            apply()

            const observer = new MutationObserver(apply)

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class'],
            })

            this.observers.push(observer)
        },

        destroy() {
            this.cleanups.forEach((cleanup) => cleanup())
            this.observers.forEach((observer) => observer.disconnect())

            this.adapter?.destroy()
            this.adapter = null
        },
    }
}