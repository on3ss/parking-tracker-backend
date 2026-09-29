import 'leaflet-draw';
import 'leaflet-draw/dist/leaflet.draw.css';

export default class LeafletMapAdapter {
    constructor({
        leaflet,
        element,
        options = {},
        type,
        disabled = false,
    }) {
        if (!leaflet) {
            throw new Error(
                'A Leaflet instance is required.',
            );
        }

        this.L = leaflet;
        this.element = element;
        this.options = options;
        this.type = type;
        this.disabled = disabled;

        this.map = null;
        this.layers = null;
        this.tiles = null;
        this.drawControl = null;
        this.scaleControl = null;

        this.geometry = null;

        this.changeListeners = [];
        this.busyListeners = [];
        this.listeners = [];

        this.destroyed = false;
    }

    mount() {
        if (this.map) {
            return this;
        }

        const center =
            Array.isArray(this.options.center) &&
                this.options.center.length === 2
                ? this.options.center
                : [0, 0];

        const zoom = Number.isFinite(this.options.zoom)
            ? this.options.zoom
            : 2;

        this.map = this.L
            .map(this.element)
            .setView(center, zoom);

        this.addTiles();
        this.addLayers();
        this.addScaleControl();

        if (!this.disabled) {
            this.configureEditing();
        }

        this.applyTheme();

        window.requestAnimationFrame(() => {
            this.resize();
        });

        return this;
    }

    addTiles() {
        if (!this.options.tiles?.url) {
            return;
        }

        this.tiles = this.L
            .tileLayer(this.options.tiles.url, {
                maxZoom: this.options.tiles.maxZoom,
                maxNativeZoom:
                    this.options.tiles.maxNativeZoom ??
                    this.options.tiles.maxZoom,
                attribution: this.options.tiles.attribution,
            })
            .addTo(this.map);
    }

    addLayers() {
        this.layers = new this.L.FeatureGroup().addTo(
            this.map,
        );
    }

    addScaleControl() {
        this.scaleControl = this.L.control.scale({
            imperial: false,
        });

        this.scaleControl.addTo(this.map);
    }

    configureEditing() {
        if (this.type === 'point') {
            this.configurePointEditing();

            return;
        }

        if (this.type === 'linestring' || this.type === 'polygon') {
            this.configureDrawing();
        }
    }

    configurePointEditing() {
        this.on(this.map, 'click', (event) => {
            this.layers.clearLayers();

            this.layers.addLayer(
                this.createMarker(event.latlng),
            );

            this.emitChange(this.getGeometry());
        });
    }

    configureDrawing() {
        const drawOptions = {
            marker: false,
            rectangle: false,
            circle: false,
            circlemarker: false,

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
        };

        this.drawControl = new this.L.Control.Draw({
            position: 'topright',

            draw: drawOptions,

            edit: {
                featureGroup: this.layers,
            },
        });

        this.map.addControl(this.drawControl);

        this.on(
            this.map,
            this.L.Draw.Event.DRAWSTART,
            () => this.emitBusy(true),
        );

        this.on(
            this.map,
            this.L.Draw.Event.DRAWSTOP,
            () => this.emitBusy(false),
        );

        this.on(
            this.map,
            this.L.Draw.Event.EDITSTART,
            () => this.emitBusy(true),
        );

        this.on(
            this.map,
            this.L.Draw.Event.EDITSTOP,
            () => this.emitBusy(false),
        );

        this.on(
            this.map,
            this.L.Draw.Event.CREATED,
            (event) => {
                this.layers.clearLayers();
                this.layers.addLayer(event.layer);

                this.emitChange(this.getGeometry());
            },
        );

        this.on(
            this.map,
            this.L.Draw.Event.EDITED,
            () => {
                this.emitChange(this.getGeometry());
            },
        );

        this.on(
            this.map,
            this.L.Draw.Event.DELETED,
            () => {
                this.emitChange(null);
            },
        );
    }

    createMarker(latlng) {
        const marker = this.L.marker(latlng, {
            draggable: !this.disabled,
        });

        if (!this.disabled) {
            marker.on('dragend', () => {
                this.emitChange(this.getGeometry());
            });
        }

        return marker;
    }

    on(target, event, handler) {
        target.on(event, handler);

        this.listeners.push(() => {
            target.off(event, handler);
        });
    }

    setGeometry(geometry, { fit = false } = {}) {
        if (this.destroyed || !this.layers) {
            return;
        }

        this.geometry = this.normalizeGeometry(
            geometry,
        );

        this.layers.clearLayers();

        if (this.geometry) {
            const geoJsonLayer = this.L.geoJSON(
                this.geometry,
                {
                    pointToLayer: (_, latlng) =>
                        this.createMarker(latlng),
                },
            );

            geoJsonLayer.eachLayer((layer) => {
                this.layers.addLayer(layer);
            });
        }

        if (fit) {
            this.fitGeometry();
        }
    }

    getGeometry() {
        const layer = this.layers?.getLayers()?.[0];

        if (!layer) {
            return null;
        }

        const precision = this.options.precision ?? 7;

        return this.normalizeGeometry(
            layer.toGeoJSON(precision).geometry,
        );
    }

    normalizeGeometry(geometry) {
        if (!geometry) {
            return null;
        }

        if (geometry.type === 'Feature') {
            return geometry.geometry ?? null;
        }

        if (geometry.type === 'FeatureCollection') {
            return (
                geometry.features?.[0]?.geometry ??
                null
            );
        }

        if (
            !geometry.type ||
            !geometry.coordinates
        ) {
            return null;
        }

        return {
            type: geometry.type,
            coordinates: geometry.coordinates,
        };
    }

    fitGeometry() {
        if (!this.map || !this.layers) {
            return;
        }

        const layers = this.layers.getLayers();

        if (layers.length === 0) {
            return;
        }

        if (
            this.type === 'point' ||
            (
                layers.length === 1 &&
                layers[0] instanceof this.L.Marker
            )
        ) {
            this.map.panTo(
                layers[0].getLatLng(),
            );

            return;
        }

        const bounds = this.layers.getBounds();

        if (!bounds.isValid()) {
            return;
        }

        this.map.fitBounds(bounds, {
            maxZoom:
                this.options.fitMaxZoom ?? 18,

            padding:
                this.options.fitPadding ?? [30, 30],
        });
    }

    onChange(callback) {
        this.changeListeners.push(callback);

        return () => {
            this.changeListeners =
                this.changeListeners.filter(
                    (listener) =>
                        listener !== callback,
                );
        };
    }

    onBusyChange(callback) {
        this.busyListeners.push(callback);

        return () => {
            this.busyListeners =
                this.busyListeners.filter(
                    (listener) =>
                        listener !== callback,
                );
        };
    }

    emitChange(geometry) {
        this.geometry =
            this.normalizeGeometry(geometry);

        this.changeListeners.forEach(
            (listener) => {
                listener(this.geometry);
            },
        );
    }

    emitBusy(value) {
        this.busyListeners.forEach(
            (listener) => {
                listener(value);
            },
        );
    }

    resize() {
        this.map?.invalidateSize();
    }

    applyTheme() {
        const dark =
            document.documentElement.classList.contains(
                'dark',
            );

        if (this.tiles) {
            const container =
                this.tiles.getContainer();

            if (container) {
                container.style.filter = dark
                    ? 'invert(1) hue-rotate(180deg) brightness(0.95) contrast(0.9)'
                    : '';
            }
        }

        if (!this.layers) {
            return;
        }

        const stroke = dark
            ? '#f5f5f5'
            : '#2563eb';

        this.layers.eachLayer((layer) => {
            if (
                layer instanceof this.L.Marker
            ) {
                return;
            }

            if (
                typeof layer.setStyle ===
                'function'
            ) {
                layer.setStyle({
                    color: stroke,
                });
            }
        });
    }

    destroy() {
        if (this.destroyed) {
            return;
        }

        this.destroyed = true;

        this.changeListeners = [];
        this.busyListeners = [];

        this.listeners.forEach((cleanup) => {
            try {
                cleanup();
            } catch {
                // Ignore cleanup errors.
            }
        });

        this.listeners = [];

        if (this.map) {
            if (this.drawControl) {
                this.map.removeControl(
                    this.drawControl,
                );
            }

            if (this.scaleControl) {
                this.map.removeControl(
                    this.scaleControl,
                );
            }

            this.map.remove();
        }

        this.map = null;
        this.layers = null;
        this.tiles = null;
        this.drawControl = null;
        this.scaleControl = null;
    }
}