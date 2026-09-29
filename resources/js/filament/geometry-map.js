import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

import LeafletMapAdapter from './leaflet-adapter.js';

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

function geometryMap({
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

        init() {
            try {
                this.adapter = new LeafletMapAdapter({
                    leaflet: L,
                    element: this.$refs.map,
                    options,
                    type,
                    disabled,
                });

                this.adapter.mount();

                this.adapter.setGeometry(this.value(), {
                    fit: true,
                });

                this.attachAdapterListeners();
                this.attachStateWatchers();
                this.watchResize();
                this.watchTheme();
            } catch (error) {
                console.error(
                    'Unable to initialize geometry map.',
                    error,
                );
            }
        },

        value() {
            if (bound) {
                const latitude = Number.parseFloat(this.latitude);
                const longitude = Number.parseFloat(this.longitude);

                if (
                    !Number.isFinite(latitude) ||
                    !Number.isFinite(longitude)
                ) {
                    return null;
                }

                return {
                    type: 'Point',
                    coordinates: [longitude, latitude],
                };
            }

            return this.state ?? null;
        },

        write(geometry) {
            if (bound) {
                const coordinates = geometry?.coordinates;

                if (!Array.isArray(coordinates)) {
                    this.latitude = null;
                    this.longitude = null;

                    return;
                }

                const [longitude, latitude] = coordinates;

                this.latitude = latitude;
                this.longitude = longitude;

                return;
            }

            this.state = geometry;
        },

        attachAdapterListeners() {
            this.cleanups.push(
                this.adapter.onChange((geometry) => {
                    this.write(geometry);
                }),
            );

            this.cleanups.push(
                this.adapter.onBusyChange((busy) => {
                    this.busy = busy;
                }),
            );
        },

        attachStateWatchers() {
            if (bound) {
                this.$watch(
                    'latitude',
                    () => this.syncExternalState(),
                );

                this.$watch(
                    'longitude',
                    () => this.syncExternalState(),
                );

                return;
            }

            this.$watch(
                'state',
                () => this.syncExternalState(),
            );
        },

        syncExternalState() {
            if (this.busy || !this.adapter) {
                return;
            }

            const external = this.value();
            const current = this.adapter.getGeometry();

            if (
                JSON.stringify(external) ===
                JSON.stringify(current)
            ) {
                return;
            }

            this.adapter.setGeometry(external, {
                fit: false,
            });
        },

        watchResize() {
            if (typeof ResizeObserver === 'undefined') {
                return;
            }

            const observer = new ResizeObserver(() => {
                this.adapter?.resize();
            });

            observer.observe(this.$refs.map);

            this.observers.push(observer);
        },

        watchTheme() {
            const applyTheme = () => {
                this.adapter?.applyTheme();
            };

            applyTheme();

            const observer = new MutationObserver(applyTheme);

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class'],
            });

            this.observers.push(observer);
        },

        destroy() {
            this.cleanups.forEach((cleanup) => cleanup());

            this.observers.forEach((observer) => {
                observer.disconnect();
            });

            this.adapter?.destroy();

            this.adapter = null;
        },
    };
}

/*
 * Register with Filament's existing Alpine instance.
 */
function register() {
    if (!window.Alpine) {
        return;
    }

    window.Alpine.data('geometryMap', geometryMap);
}

/*
 * Keep the factory globally available because x-data evaluates
 * geometryMap(...) as a JavaScript expression.
 *
 * This also makes registration independent of Alpine's initialization
 * timing.
 */
window.geometryMap = geometryMap;

if (window.Alpine) {
    register();
} else {
    document.addEventListener('alpine:init', register, {
        once: true,
    });
}