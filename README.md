# Parking Tracker

Backend for a city parking discovery and availability tracking platform.

The system provides APIs for discovering parking facilities and street parking, viewing parking details, reporting availability, viewing availability history, and managing user favorites.

The backend is built as a Laravel application with PostgreSQL/PostGIS and Sanctum authentication.

---

## Development Status

The core backend is implemented and the full test suite is passing.

### Implemented

* User registration and authentication
* Sanctum API authentication
* Parking facility discovery
* Street parking discovery
* Combined parking search
* Availability filtering
* Provider filtering
* Location/radius search
* PostGIS distance calculation
* Parking sorting and pagination
* Parking detail endpoints
* Public parking identifiers
* Availability reporting
* Occupancy history
* User favorites
* API resources and validation
* Feature and API tests

### Planned

* Parking/provider administration
* Automated availability sources
* Sensor/camera integrations
* External parking-provider feeds
* Parking data/report moderation
* Notifications
* Advanced geospatial search
* API hardening and production observability
* Production infrastructure

---

# 1. Technology Stack

| Component                      | Technology                    |
| ------------------------------ | ----------------------------- |
| Backend                        | Laravel                       |
| Language                       | PHP                           |
| Database                       | PostgreSQL                    |
| Geospatial                     | PostGIS                       |
| Geospatial Laravel integration | `clickbar/laravel-magellan`   |
| Authentication                 | Laravel Sanctum               |
| Testing                        | Pest                          |
| Local development              | Laravel Sail / Docker Compose |
| API                            | REST / JSON                   |

Kubernetes is **not currently required for local development**. The initial development environment uses Docker Compose/Sail. Kubernetes can be introduced later as a deployment concern.

---

# 2. Core Domain

The parking domain currently contains two parking types.

### Parking facilities

Managed or structured parking locations such as:

* parking lots
* parking buildings
* commercial parking facilities
* other managed parking locations

Represented by:

```text
ParkingFacility
```

### Street parking

Individual or grouped street-side parking locations.

Represented by:

```text
StreetParking
```

These remain separate models because their underlying data and behavior are different.

---

# 3. Public Parking Identifiers

Database IDs are only unique within their respective parking tables.

For the API, parking resources therefore use a globally distinguishable identifier:

```text
facility:1
street:1
```

Examples:

```http
GET /api/v1/parking/facility:1
GET /api/v1/parking/street:1
```

`ParkingIdentifier` is the central mechanism for generating public identifiers and determining parking type.

The inverse operation is handled by:

```text
ResolveParkingIdentifier
```

This keeps public identity consistent across the API.

---

# 4. Architecture

The backend follows a lightweight action-oriented architecture.

```text
HTTP Request
     │
     ▼
Form Request
(validation + normalization)
     │
     ▼
DTO / primitive input
     │
     ▼
Action
(application/domain use case)
     │
     ▼
Eloquent Models / PostgreSQL / PostGIS
     │
     ▼
API Resource
(JSON representation)
```

## Controllers

Controllers are intentionally thin.

They are responsible for:

* receiving the HTTP request
* obtaining validated input
* invoking the appropriate action
* returning an API resource

Business logic should not be placed in controllers.

## Form Requests

Form Requests handle:

* HTTP validation
* request normalization
* converting HTTP input into application input where appropriate

They should not contain domain/business logic.

## Actions

Actions represent meaningful application use cases.

Examples:

```text
SearchParking
ResolveParkingIdentifier
ReportParkingAvailability
GetParkingAvailabilityHistory
FavoriteParking
UnfavoriteParking
ListFavorites
```

Actions should contain the behavior necessary to perform their specific use case.

Generic service, manager, repository, or transformer layers should not be introduced unless a real independent responsibility emerges.

## DTOs

DTOs are used where an operation has a meaningful structured input or result.

Current examples include:

```text
LoginData
RegisterUserData

SearchParkingData
ReportParkingAvailabilityData
GetParkingAvailabilityHistoryData
ParkingSearchResult
```

DTOs should not be created merely to wrap one or two trivial parameters.

## API Resources

Resources define the external API representation.

Examples:

```text
ParkingResource
ParkingDetailResource
FavoriteResource
OccupancyReportResource
```

Resources should remain presentation-oriented and should not contain domain behavior.

---

# 5. Search

Parking search is implemented by:

```text
SearchParking
```

It supports:

* parking type
* availability
* provider
* latitude
* longitude
* radius
* sorting
* pagination

Example:

```http
GET /api/v1/parking
```

Example:

```http
GET /api/v1/parking?type=facility
```

Location-based search:

```http
GET /api/v1/parking?latitude=25.5779199&longitude=91.8837004&radius=2000
```

Supported sort fields include:

```text
distance
-distance

name
-name

capacity
-capacity

available_spaces
-available_spaces
```

When coordinates are supplied without an explicit sort, distance is used as the default ordering.

Without coordinates, name is used as the default ordering.

---

# 6. Geospatial Search

PostGIS is used for spatial calculations.

The backend stores geographic coordinates and uses PostGIS functions for distance calculations.

The search flow is conceptually:

```text
latitude + longitude
        │
        ▼
PostGIS Point
        │
        ▼
distance calculation
        │
        ▼
radius filtering
        │
        ▼
parking results
```

The Laravel application uses:

```text
clickbar/laravel-magellan
```

for improved PostGIS developer experience.

---

# 7. Availability

Availability has two related concepts.

### Current availability

The parking entity stores the application's current best-known availability:

```text
available_spaces
availability_status
availability_updated_at
```

### Occupancy reports

Every availability report creates an immutable historical record.

Conceptually:

```text
Availability report
       │
       ├── occupancy_reports record
       │
       └── current parking availability update
```

This means historical reports are preserved while the parking entity exposes the latest known state.

---

# 8. Availability Status

Availability currently uses:

```text
UNKNOWN
AVAILABLE
LIMITED
FULL
```

The status is calculated from the parking capacity and current available spaces.

Availability validation is performed before updating the parking state.

---

# 9. Availability Sources

The backend models availability sources using:

```text
ParkingSource
```

Current source types include:

```text
USER
OPERATOR
SENSOR
CAMERA
SYSTEM
```

The current API supports user-reported availability.

The additional sources provide a foundation for future automated integrations.

---

# 10. Reporting Availability

Availability can be reported against a public parking identifier.

Conceptually:

```http
POST /api/v1/parking/{parking}/availability
```

Example identifier:

```text
facility:1
```

A report contains information such as:

```text
available_spaces
occupied_spaces
confidence
```

The backend:

1. Resolves the parking identifier.
2. Validates the supplied availability.
3. Creates an immutable occupancy report.
4. Updates current availability.
5. Updates the availability timestamp.
6. Returns the updated parking resource.

The operation is performed transactionally.

---

# 11. Availability History

Historical reports can be retrieved for a parking resource.

Conceptually:

```http
GET /api/v1/parking/{parking}/availability/history
```

Results are paginated and ordered by the most recent report first.

---

# 12. Favorites

Authenticated users can favorite parking resources.

Conceptually:

```http
POST   /api/v1/parking/{parking}/favorite
DELETE /api/v1/parking/{parking}/favorite
GET    /api/v1/favorites
```

Favorites use the same public parking identifiers:

```text
facility:1
street:1
```

The favorite relationship supports both parking types.

Duplicate favorites are prevented through the `firstOrCreate` behavior and database/domain constraints.

---

# 13. Authentication

The mobile/API authentication mechanism is Laravel Sanctum.

The authentication flow is:

```text
Register / Login
       │
       ▼
Sanctum token
       │
       ▼
Authenticated API requests
```

Protected endpoints use the authenticated user supplied by Laravel's request authentication layer.

---

# 14. API Conventions

API endpoints are versioned:

```text
/api/v1/...
```

Parking resources use public identifiers rather than exposing ambiguous numeric IDs across different parking types.

Example:

```json
{
    "id": "facility:1",
    "type": "facility"
}
```

or:

```json
{
    "id": "street:1",
    "type": "street"
}
```

---

# 15. Testing

The project uses Pest.

Run the complete test suite:

```bash
sail test
```

Run a specific test class:

```bash
sail test --filter=ParkingIndexTest
```

Run parking-related tests:

```bash
sail test --filter=Parking
```

Tests cover the API and application behavior, including:

* authentication
* parking search
* parking details
* availability reporting
* availability history
* favorites
* public parking identifiers

The full test suite should pass before merging changes.

---

# 16. Local Development

The project uses Laravel Sail and Docker Compose for local development.

Start the environment:

```bash
./vendor/bin/sail up -d
```

or, if the project provides the Sail alias:

```bash
sail up -d
```

Stop the environment:

```bash
sail down
```

View application logs:

```bash
sail logs -f
```

Run Artisan:

```bash
sail artisan <command>
```

Run Composer:

```bash
sail composer <command>
```

Run tests:

```bash
sail test
```

---

# 17. Database

The application uses PostgreSQL.

PostGIS is required for the geospatial functionality.

After starting the development environment, run:

```bash
sail artisan migrate
```

For a fresh development database:

```bash
sail artisan migrate:fresh --seed
```

Do not use destructive database commands against a database containing data that needs to be preserved.

---

# 18. Seed Data

Development seed data should remain deterministic where possible.

This is particularly useful for geospatial testing because parking locations can be placed at known coordinates and used consistently by feature tests.

---

# 19. Project Structure

Relevant application structure:

```text
app/
├── Actions/
│   ├── Auth/
│   └── Parking/
│
├── Data/
│   ├── Auth/
│   └── Parking/
│
├── Enums/
│
├── Exceptions/
│
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │
│   ├── Requests/
│   │   └── Api/
│   │       └── V1/
│   │
│   └── Resources/
│
├── Models/
│
└── Support/
    └── Parking/
```

Parking-specific public identity support lives under:

```text
app/Support/Parking/
```

rather than the DTO directory.

---

# 20. Development Principles

### Prefer simple solutions

Do not introduce an abstraction simply because it is theoretically reusable.

Before adding a:

* repository
* service
* manager
* factory
* transformer
* generic interface

ask whether there is a real independent responsibility requiring it.

### Keep actions meaningful

An Action should represent a useful application operation.

Good:

```text
SearchParking
ReportParkingAvailability
FavoriteParking
GetParkingAvailabilityHistory
```

Avoid actions that merely delegate one method call without adding meaningful behavior.

### Keep HTTP concerns at the HTTP boundary

Validation and normalization belong in Form Requests.

Domain/application behavior belongs in Actions and domain models.

### Keep identity centralized

Do not duplicate:

```php
instanceof ParkingFacility
    ? 'facility'
    : 'street'
```

throughout the application.

Use:

```php
ParkingIdentifier::for($parking)
ParkingIdentifier::type($parking)
```

### Avoid premature optimization

The current implementation favors straightforward Eloquent/PostGIS queries and clear application behavior.

Performance optimizations should be introduced when measurements or realistic workload requirements justify them.

---

# 21. Current Backend Roadmap

## Next development areas

### Parking management

* Create parking facilities
* Update parking facilities
* Deactivate parking facilities
* Create street parking
* Update street parking
* Provider management
* Location management

### Availability infrastructure

* Availability expiration
* Stale availability detection
* Source-priority rules
* Conflict resolution
* Automated ingestion
* Sensor integration
* Camera integration
* External provider feeds

### Data quality

* Incorrect parking reports
* Availability correction reports
* Moderation
* Duplicate detection
* Anomaly detection
* Source reliability

### Geospatial capabilities

* Bounding-box search
* Map viewport search
* Spatial clustering
* Route-aware parking search

### Notifications

* Availability alerts
* Favorite parking alerts
* Push notification infrastructure
* Notification preferences

### Production readiness

* API rate limiting
* Authorization policies
* Standardized error responses
* API documentation
* Logging
* Monitoring
* Metrics
* Caching
* Queues
* Scheduled jobs
* Backup/restore strategy

### Infrastructure

Local development currently uses Docker Compose/Sail.

Kubernetes is intentionally deferred until the application and deployment requirements justify it.

---

# 22. Development Workflow

For a typical feature:

```text
1. Define the domain behavior
        ↓
2. Define/modify the database model
        ↓
3. Add migration
        ↓
4. Add/update model
        ↓
5. Add Action
        ↓
6. Add Form Request if HTTP validation is required
        ↓
7. Add/update API Resource
        ↓
8. Add Controller/Route
        ↓
9. Add Pest tests
        ↓
10. Run focused tests
        ↓
11. Run full test suite
```

Before considering a change complete:

```bash
sail test
```

must pass.

---

# 23. Guiding Architecture

The project intentionally favors:

```text
Laravel
    +
PostgreSQL/PostGIS
    +
Sanctum
    +
Actions
    +
Focused DTOs
    +
Form Requests
    +
API Resources
    +
Pest
```

over a heavily layered architecture.

The goal is to keep the backend understandable, testable, and inexpensive to operate while leaving room for future geospatial, real-time availability, and automated data-source integrations.
