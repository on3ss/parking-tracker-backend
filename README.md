# 🅿️ Parking Tracker

A Laravel backend for a city parking discovery and availability tracking platform — find parking, check what's open, report availability, and save favorites.

## What it does

Parking Tracker models two distinct kinds of parking:

* **Parking facilities** — managed lots, garages, and other provider-operated parking
* **Street parking** — on-street parking segments/spaces

The API supports:

* parking discovery and nearby search
* facility/street parking details
* availability reporting and history
* source-aware availability evaluation
* favorites
* Sanctum authentication
* provider/tenant management through Filament
* PostGIS-backed location and distance queries

**Stack:** Laravel + PHP + PostgreSQL/PostGIS + Sanctum + Filament + Pest + Clickbar Magellan, running locally with Laravel Sail/Docker Compose.

---

## Current architecture

The backend deliberately uses a small application architecture:

```text
HTTP
 │
 ├── Form Request
 │       └── validation / normalization
 │
 ├── Controller
 │
 ├── DTO
 │
 └── Action
        │
        ├── domain/application actions
        └── Eloquent + PostgreSQL/PostGIS
                │
                └── API Resource
```

Controllers stay thin. HTTP validation belongs in Form Requests. Business/use-case behavior belongs in Actions. Eloquent remains the persistence layer.

There are intentionally no repositories, generic service classes, managers, or transformers unless an independent responsibility justifies one.

### Availability reporting flow

Availability reporting is split into focused actions:

```text
ReportParkingAvailability
        │
        ├── ResolveParkingIdentifier
        │
        ├── RecordOccupancyObservation
        │       │
        │       └── ComputeReportConfidence
        │
        └── ApplyOccupancyToParking
                │
                └── EvaluateOccupancyReport
```

The operation runs in a database transaction and locks the parking record while the observation is evaluated and applied.

---

## Parking identity

Facility IDs and street-parking IDs are only unique inside their respective tables. The public API therefore uses a globally distinguishable identifier:

```text
facility:1
street:1
```

Examples:

```http
GET /api/v1/parking/facility:1
GET /api/v1/parking/street:1
POST /api/v1/parking/facility:1/availability
GET /api/v1/parking/street:1/availability/history
```

Public identity is centralized in:

* `ParkingIdentifier` — creates/parses the public identifier
* `ResolveParkingIdentifier` — resolves it to the corresponding Eloquent model

Do not duplicate facility/street identifier logic elsewhere in the application.

---

## Availability model

The application separates **observations** from **current availability**.

Every availability report creates an immutable `occupancy_reports` record. If the observation is accepted, the current parking state is updated.

```text
Availability observation
        │
        ├── immutable OccupancyReport
        │
        └── current parking state
             ├── available_spaces
             ├── availability_status
             ├── availability_updated_at
             ├── availability_source
             └── availability_report_id
```

### Reported vs computed confidence

Confidence has two distinct meanings:

| Field                 | Meaning                                     |
| --------------------- | ------------------------------------------- |
| `reported_confidence` | Confidence supplied by the reporting source |
| `computed_confidence` | Confidence calculated by the application    |

The application does not blindly trust the supplied value. `ComputeReportConfidence` applies the source-specific confidence ceiling and incorporates corroboration and recency.

The source model currently supports:

```text
USER · OPERATOR · SENSOR · CAMERA · SYSTEM
```

Each source has:

* a trust level
* a freshness TTL
* a maximum self-reported confidence ceiling

Current source policy:

| Source   | Trust | Freshness TTL | Confidence ceiling |
| -------- | ----: | ------------: | -----------------: |
| SYSTEM   |   100 |         1 min |               1.00 |
| OPERATOR |    80 |        30 min |               0.95 |
| SENSOR   |    60 |         2 min |               0.90 |
| CAMERA   |    50 |         5 min |               0.85 |
| USER     |    20 |        15 min |               0.60 |

When competing observations are received, `EvaluateOccupancyReport` considers:

1. whether the current observation is stale
2. source trust
3. computed confidence when the sources have the same trust tier

This means a lower-trust fresh observation does not automatically overwrite a higher-trust fresh observation.

### Reporting availability

```http
POST /api/v1/parking/facility:1/availability
```

Request fields:

| Field                 | Required | Description                                         |
| --------------------- | -------- | --------------------------------------------------- |
| `available_spaces`    | yes      | Current number of available spaces                  |
| `occupied_spaces`     | no       | Occupied spaces; derived from capacity when omitted |
| `reported_confidence` | no       | Source-supplied confidence from 0 to 1              |

The backend:

1. resolves the public parking identifier
2. locks the parking record
3. validates capacity and occupancy values
4. creates an immutable occupancy observation
5. computes confidence
6. evaluates whether the observation should become current state
7. updates current availability when accepted
8. returns the parking resource and report metadata

A report may therefore be recorded without becoming the current authoritative availability.

### Availability history

```http
GET /api/v1/parking/facility:1/availability/history
GET /api/v1/parking/street:1/availability/history
```

History is returned newest first and is based on the immutable occupancy observations.

---

## API

All API endpoints are versioned under `/api/v1`.

### Authentication

Public:

```http
POST /api/v1/auth/register
POST /api/v1/auth/login
```

Authenticated:

```http
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

Authentication uses Laravel Sanctum bearer tokens.

### Parking discovery

```http
GET /api/v1/parking
GET /api/v1/parking/nearby
GET /api/v1/parking/{parking}
```

The parking index supports:

* facility/street type filtering
* availability filtering
* provider filtering
* coordinate-based searches
* radius filtering
* distance calculation
* sorting
* pagination

Example:

```http
GET /api/v1/parking?type=street&filter[availability]=AVAILABLE&latitude=25.5779199&longitude=91.8837004&radius=2000
```

Nearby search:

```http
GET /api/v1/parking/nearby?latitude=25.5779199&longitude=91.8837004&radius=2000
```

PostGIS handles spatial distance calculations.

### Favorites

Authenticated users can manage favorites using the same public parking identifier:

```http
GET    /api/v1/favorites
POST   /api/v1/favorites/{parking}
DELETE /api/v1/favorites/{parking}
```

---

## Provider panel and tenancy

The provider panel uses Filament's native tenancy support.

A user can belong to multiple parking providers through `ProviderMembership`. The active provider becomes the tenant for provider-panel operations.

The provider panel is registered with:

```php
->tenant(ParkingProvider::class, ownershipRelationship: 'provider')
```

Provider-owned resources therefore use Filament's tenant context rather than duplicating tenant query scoping inside every resource.

The project currently keeps provider membership and tenancy concerns separate from the public mobile API.

---

## Geospatial data

PostgreSQL/PostGIS is used for spatial storage and querying.

Clickbar Magellan provides the application-level geometry types used by the models and seeders, including:

* `Point`
* `LineString`

The Shillong development seeder uses Magellan geometry objects rather than raw PostGIS SQL.

The project currently uses standard PostgreSQL/PostGIS with Laravel Sail. Kubernetes and distributed database infrastructure are intentionally deferred.

---

## Quick start

Start the development environment:

```bash
./vendor/bin/sail up -d
# or:
sail up -d
```

Run migrations:

```bash
sail artisan migrate
```

Create a fresh development database with seed data:

```bash
sail artisan migrate:fresh --seed
```

Run the test suite:

```bash
sail test
```

Useful commands:

```bash
sail down
sail logs -f
sail artisan <command>
sail composer <command>

# focused test
sail test --filter=ParkingIndexTest

# parking-related tests
sail test --filter=Parking
```

Do not run destructive database commands against data you need to keep.

---

## Testing

The project uses Pest.

The test suite covers the main application boundaries, including:

* authentication and Sanctum
* parking search and nearby discovery
* public parking identifiers
* parking detail resolution
* availability reporting
* occupancy history
* availability validation
* source/trust-based availability evaluation
* favorites
* provider/tenancy behavior

Before considering a change complete:

```bash
sail test
```

The full suite should pass before merging.

---

## Project structure

```text
app/
├── Actions/
│   ├── Auth/
│   └── Parking/
├── Data/
│   ├── Auth/
│   └── Parking/
├── Enums/
├── Exceptions/
├── Filament/
│   ├── Admin/
│   ├── Provider/
│   └── Support/
├── Http/
│   ├── Controllers/
│   │   └── Api/V1/
│   ├── Requests/
│   └── Resources/
├── Models/
└── Support/

database/
├── factories/
├── migrations/
└── seeders/

tests/
├── Feature/
└── Unit/
```

Representative application actions:

```text
Auth/
  AuthenticateUser
  RegisterUser
  LogoutUser

Parking/
  SearchParking
  ResolveParkingIdentifier
  ReportParkingAvailability
  RecordOccupancyObservation
  ComputeReportConfidence
  EvaluateOccupancyReport
  ApplyOccupancyToParking
  GetParkingAvailabilityHistory
  FavoriteParking
  UnfavoriteParking
  ListFavorites
```

---

## Design principles

### Keep the domain explicit

Parking facilities and street parking are different domain concepts. They share application behavior where appropriate, but are not collapsed into an artificial generic `Parking` model.

### Keep HTTP concerns at the boundary

Form Requests handle request validation and normalization. Controllers coordinate HTTP input/output. Actions implement application behavior.

### Keep Actions meaningful

An Action should represent a real use case or independent application responsibility. Avoid adding layers simply for architectural appearance.

### Keep observations immutable

An occupancy report represents what a source observed at a particular point in time. Historical observations should not be silently rewritten.

### Centralize public identity

Use `ParkingIdentifier` and `ResolveParkingIdentifier` instead of repeating facility/street identifier logic.

### Prefer database consistency

Availability reporting uses transactions and row locking so concurrent reports cannot blindly overwrite one another.

### Avoid premature infrastructure

The current deployment target is simple Docker Compose/Sail. Kubernetes can be introduced when there is an actual operational requirement for it.

---

## Typical feature workflow

When adding a feature:

```text
1. Define the domain behavior
2. Update/add the model and migration
3. Add an Action for meaningful application behavior
4. Add a Form Request when HTTP validation is required
5. Add/update DTOs where structured input is useful
6. Add/update API Resources
7. Wire the controller and route
8. Add Pest coverage
9. Run focused tests
10. Run the full suite
```

---

## Roadmap

| Area                        | Direction                                                                                   |
| --------------------------- | ------------------------------------------------------------------------------------------- |
| Parking management          | Provider/admin CRUD and lifecycle management                                                |
| Availability infrastructure | Expiration, automated ingestion, richer conflict resolution                                 |
| Data quality                | Incorrect-report detection, moderation, anomaly detection and source reliability            |
| Geospatial                  | Bounding boxes, viewport search, clustering and route-aware search                          |
| Notifications               | Availability and favorite-based alerts                                                      |
| Production readiness        | Rate limiting, authorization, API documentation, observability, caching, queues and backups |
| Infrastructure              | Kubernetes when deployment requirements justify it                                          |

---

## Why this architecture

Parking Tracker intentionally combines Laravel's conventions with a small number of focused application patterns:

**Laravel + PostgreSQL/PostGIS + Sanctum + Filament + Actions + focused DTOs + Form Requests + API Resources + Pest.**

The goal is a backend that remains easy to understand and test while providing a solid foundation for real-time availability, multiple data sources, geospatial discovery, provider management, and future mobile clients.
