# 🅿️ Parking Tracker

A Laravel backend for a city parking discovery and availability tracking platform — find parking, check availability, report occupancy, and save favorites.

## What it does

Parking Tracker models two distinct kinds of parking:

- **Parking facilities** — managed lots, garages, and other provider-operated parking
- **Street parking** — on-street parking segments/spaces

The backend provides:

- parking discovery and nearby search
- parking detail lookup
- availability reporting and history
- favorites
- Sanctum authentication
- provider/tenant management through Filament
- PostGIS-backed location and distance queries

**Stack:** PHP 8.3 + Laravel 13 + PostgreSQL/PostGIS + Sanctum + Filament 5 + Pest 5 + Clickbar Magellan, with Laravel Sail/Docker for local development.

---

## Architecture

The application uses a small, action-oriented architecture:

```text
HTTP
 │
 ├── Form Request
 │      └── validation / normalization
 │
 ├── Controller
 │
 ├── DTO
 │
 └── Action
        │
        ├── application / domain behavior
        └── Eloquent + PostgreSQL/PostGIS
                │
                └── API Resource
```

The boundaries are intentional:

- **Form Requests** handle HTTP validation and normalization.
- **Controllers** coordinate HTTP input and output.
- **DTOs** carry structured application input and results.
- **Actions** represent meaningful use cases or focused application responsibilities.
- **Eloquent models** provide persistence and relationships.
- **API Resources** define the HTTP representation.

The project avoids generic repositories, service managers, and other abstraction layers unless they provide an independently justified responsibility.

### Availability reporting

Availability reporting is deliberately split into focused operations:

```text
ReportParkingAvailability
        │
        ├── ResolveParkingIdentifier
        │
        ├── RecordOccupancyObservation
        │
        └── ApplyOccupancyToParking
```

The report operation runs inside a database transaction and resolves the parking record with a row lock before validating and applying the observation.

---

## Parking identity

Facility IDs and street-parking IDs are only unique within their respective tables. The public API therefore uses a type-qualified identifier:

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

- `ParkingIdentifier` — creates and parses public identifiers
- `ResolveParkingIdentifier` — resolves an identifier to the corresponding parking model

Facility/street identifier logic should not be duplicated elsewhere.

---

## Availability model

The application separates **occupancy observations** from **current availability**.

Every availability report creates an immutable `OccupancyReport`. The current parking state is then updated from that report.

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

### Availability reporting

```http
POST /api/v1/parking/facility:1/availability
```

Request fields:

| Field | Required | Description |
| --- | --- | --- |
| `available_spaces` | yes | Number of available spaces |
| `occupied_spaces` | no | Number of occupied spaces; derived from capacity when omitted |

The backend:

1. resolves the public parking identifier
2. locks the parking record
3. validates capacity and occupancy values
4. creates an immutable occupancy observation
5. updates the parking's current availability
6. derives the current availability status
7. returns the resulting parking/report data

Validation ensures that:

- parking capacity is set
- available spaces are non-negative
- occupied spaces are non-negative
- available spaces do not exceed capacity
- occupied spaces do not exceed capacity
- occupied + available spaces do not exceed capacity

### Availability status

Current availability status is derived from capacity and available spaces.

The `AvailabilityStatus` enum represents the resulting state, including cases such as:

- `UNKNOWN`
- `FULL`
- `LIMITED`
- `AVAILABLE`

### Availability history

```http
GET /api/v1/parking/facility:1/availability/history
GET /api/v1/parking/street:1/availability/history
```

History is based on the immutable occupancy observations and is returned newest first.

### Parking sources

Availability observations can originate from:

```text
USER · OPERATOR · SENSOR · CAMERA · SYSTEM
```

The source is stored with the occupancy observation and current availability state.

---

## API

All application API endpoints are versioned under `/api/v1`.

### Authentication

Public endpoints:

```http
POST /api/v1/auth/register
POST /api/v1/auth/login
```

Authenticated endpoints:

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

Parking search supports the filtering, sorting, pagination, and geospatial query capabilities implemented by the current API, including type, availability, provider, coordinates, radius, and distance-based queries.

Example:

```http
GET /api/v1/parking?type=street&filter[availability]=AVAILABLE&latitude=25.5779199&longitude=91.8837004&radius=2000
```

Nearby search:

```http
GET /api/v1/parking/nearby?latitude=25.5779199&longitude=91.8837004&radius=2000
```

PostGIS is used for spatial storage and distance calculations.

### Favorites

Authenticated users can manage favorites using the public parking identifier:

```http
GET    /api/v1/favorites
POST   /api/v1/favorites/{parking}
DELETE /api/v1/favorites/{parking}
```

### API documentation

The project includes Scribe for API documentation generation.

---

## Provider panel and tenancy

The provider panel uses Filament's native tenancy support.

A user can belong to multiple parking providers through `ProviderMembership`. The active provider becomes the tenant for provider-panel operations.

The provider panel is configured with:

```php
->tenant(ParkingProvider::class, ownershipRelationship: 'provider')
```

Shared parking resources are registered with both the admin and provider panels where appropriate. Provider-owned operations rely on Filament's tenant context rather than duplicating tenant query scoping throughout every resource.

The public API and provider-panel tenancy remain separate concerns.

---

## Filament panels

The project uses Filament 5 for administrative and provider-facing interfaces.

The Filament structure separates:

- shared parking resources
- admin-only resources
- provider-panel resources
- shared forms and infolists
- relation managers
- panel-specific configuration

Parking resources are shared where their behavior is common to both panels, while panel-specific resources remain under their respective panel namespaces.

---

## Geospatial data

PostgreSQL/PostGIS is the spatial persistence layer.

Clickbar Magellan provides application-level geometry types used by the models and seeders, including:

- `Point`
- `LineString`

The development data includes deterministic Shillong parking/location data.

The application currently uses PostgreSQL/PostGIS through Laravel Sail. Distributed database infrastructure and Kubernetes are intentionally deferred until operational requirements justify them.

---

## Quick start

### Requirements

- Docker
- Docker Compose
- Git

Laravel Sail provides the application development environment.

### Install

Clone the repository and install dependencies:

```bash
git clone https://github.com/on3ss/parking-tracker-backend.git
cd parking-tracker-backend

composer install
cp .env.example .env
php artisan key:generate
```

Start Sail:

```bash
./vendor/bin/sail up -d
```

Or, if the Sail alias is configured:

```bash
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
sail test --filter=Parking

# full suite
sail test
```

Do not run destructive database commands against data that must be preserved.

---

## Testing

The project uses Pest and separates tests by purpose:

```text
tests/
├── Feature/
│   ├── Api/
│   └── Filament/
├── Integration/
│   ├── Actions/
│   ├── Filament/
│   ├── Persistence/
│   └── PostGIS/
└── Unit/
    ├── Enums/
    └── Support/
```

### Feature tests

Feature tests exercise externally visible application behavior, including:

- authentication endpoints
- parking API endpoints
- parking discovery
- availability endpoints
- favorites
- Filament resources and panel behavior

### Integration tests

Integration tests cover application actions and infrastructure boundaries, including:

- parking actions
- occupancy reporting
- parking identifier resolution
- favorites
- persistence constraints
- occupancy report immutability
- PostGIS geometry persistence
- Filament integration

### Unit tests

Unit tests cover small deterministic pieces of domain/support behavior, including:

- availability status rules
- public parking identifier parsing/formatting
- GeoJSON support

Run the complete suite before considering a change complete:

```bash
sail test
```

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
│   ├── Actions/
│   ├── Admin/
│   ├── Concerns/
│   ├── Forms/
│   ├── Infolists/
│   ├── Provider/
│   ├── RelationManagers/
│   ├── Resources/
│   ├── Schemas/
│   └── Support/
├── Http/
│   ├── Controllers/
│   │   └── Api/V1/
│   ├── Requests/
│   │   ├── Api/
│   │   ├── Auth/
│   │   └── Parking/
│   └── Resources/
├── Models/
│   └── Concerns/
├── Providers/
│   └── Filament/
└── Support/
    ├── Geo/
    └── Parking/

database/
├── factories/
├── migrations/
└── seeders/

tests/
├── Feature/
├── Integration/
└── Unit/
```

### Representative actions

Authentication:

```text
AuthenticateUser
GetAuthenticatedUser
RegisterUser
LogoutUser
```

Parking:

```text
SearchParking
ResolveParkingIdentifier
ReportParkingAvailability
RecordOccupancyObservation
ApplyOccupancyToParking
GetParkingAvailabilityHistory
FavoriteParking
UnfavoriteParking
ListFavorites
UpdateStreetParking
```

---

## Design principles

### Keep the domain explicit

Parking facilities and street parking are distinct domain concepts. They share application behavior where appropriate, but are not collapsed into an artificial generic parking model.

### Keep HTTP concerns at the boundary

Form Requests handle request validation and normalization. Controllers coordinate HTTP concerns. Actions implement application behavior.

### Keep Actions meaningful

An Action should represent a real use case or focused application responsibility. Avoid adding layers solely for architectural appearance.

### Keep observations immutable

An occupancy report represents what a source observed at a particular point in time. Historical observations should not be silently rewritten.

### Centralize public identity

Use `ParkingIdentifier` and `ResolveParkingIdentifier` instead of repeating facility/street identifier logic.

### Prefer database consistency

Availability reporting uses transactions and row locking so concurrent reports are handled consistently.

### Prefer focused tests

Pure deterministic rules belong in Unit tests. Application behavior involving the database belongs in Integration tests. HTTP and panel behavior belongs in Feature tests.

### Avoid premature infrastructure

The current development and deployment foundation is Laravel + PostgreSQL/PostGIS + Sail/Docker. Kubernetes or distributed infrastructure should be introduced only when there is an operational requirement for it.

---

## Typical feature workflow

When adding a feature:

```text
1. Define the domain/application behavior
2. Update or add the model and migration
3. Add an Action for meaningful application behavior
4. Add a Form Request when HTTP validation is required
5. Add/update DTOs where structured input is useful
6. Add/update API Resources
7. Wire the controller and route
8. Add the appropriate Unit / Integration / Feature tests
9. Run focused tests
10. Run the full suite
```

---

## Roadmap

| Area | Direction |
| --- | --- |
| Parking management | Provider/admin CRUD and lifecycle management |
| Availability infrastructure | Expiration, automated ingestion, richer observation handling |
| Data quality | Incorrect-report detection, moderation, anomaly detection |
| Geospatial | Bounding boxes, viewport search, clustering and route-aware search |
| Notifications | Availability and favorite-based alerts |
| Production readiness | Rate limiting, authorization, observability, caching, queues and backups |
| Infrastructure | Kubernetes when deployment requirements justify it |

---

## Why this architecture

Parking Tracker intentionally combines Laravel conventions with a small number of focused application patterns:

**Laravel + PostgreSQL/PostGIS + Sanctum + Filament + Actions + DTOs + Form Requests + API Resources + Pest.**

The goal is a backend that remains easy to understand and test while providing a solid foundation for geospatial parking discovery, availability reporting, immutable observation history, provider management, and future mobile clients.