# 🅿️ Parking Tracker

A Laravel backend for a city parking discovery and availability tracking platform — find parking, check what's open, report availability, and save your favorites.

## What it does

Parking Tracker helps people find and track parking in a city. It covers two kinds of parking:

* **Parking facilities** — lots, garages, and other managed/commercial parking
* **Street parking** — individual or grouped on-street spots

The API lets clients search for parking nearby, view details, report live availability, look at availability history, and manage a personal list of favorites.

**Stack:** Laravel + PostgreSQL/PostGIS + Sanctum auth + Pest tests, running locally via Laravel Sail.

> No Kubernetes here (yet). Local dev is just Docker Compose/Sail — K8s can come later once there's an actual deployment need for it.

---

## Status

✅ **The core backend is built and the full test suite is green.**

<details>
<summary><strong>Already working</strong></summary>

* User registration & login, with Sanctum API auth
* Facility + street parking discovery, and combined search
* Filtering by parking type, availability, and provider
* Location/radius filtering
* PostGIS-powered distance calculation
* Sorting & pagination
* Parking detail lookups via public IDs
* Availability reporting + occupancy history
* Favorites
* API resources, validation, and a full feature/API test suite

</details>

<details>
<summary><strong>On the roadmap</strong></summary>

* Admin tools for parking & providers
* Automated availability sources (sensors, cameras, external feeds)
* Report moderation
* Notifications
* Advanced geospatial search (bounding box, viewport, clustering)
* Production hardening: rate limiting, auth policies, observability, caching, queues, backups

</details>

---

## Quick start

```bash
# spin up the environment
./vendor/bin/sail up -d
# (or just `sail up -d` if you've got the alias)

# set up the database
sail artisan migrate

# want a fresh DB with sample data?
sail artisan migrate:fresh --seed

# run the tests
sail test
```

Useful day-to-day commands:

```bash
sail down
sail logs -f
sail artisan <command>
sail composer <command>

# run one test class
sail test --filter=ParkingIndexTest

# run everything parking-related
sail test --filter=Parking
```

⚠️ Don't run destructive DB commands (`migrate:fresh`, etc.) against a database with data you care about.

---

## How parking is identified

Facility IDs and street-parking IDs both start counting from 1 in their own tables, so a raw database ID alone is ambiguous.

The API solves this with a **public identifier** that's globally unique:

```text
facility:1
street:1
```

```http
GET /api/v1/parking/facility:1
GET /api/v1/parking/street:1
```

Two small classes handle this so the logic never gets duplicated around the codebase:

* `ParkingIdentifier` — builds the public ID and tells you the parking type
* `ResolveParkingIdentifier` — the reverse: turns `facility:1` back into a real record

If you ever find yourself writing:

```php
$parking instanceof ParkingFacility ? 'facility' : 'street'
```

somewhere — stop, and use `ParkingIdentifier` instead.

---

## Architecture, in one picture

```text
HTTP Request
     │
     ▼
Form Request        ← validation & normalization
     │
     ▼
DTO / primitive input
     │
     ▼
Action               ← the actual use case
     │
     ▼
Eloquent / PostgreSQL / PostGIS
     │
     ▼
API Resource         ← JSON shape returned to the client
```

**The short version:** controllers stay thin, validation lives in Form Requests, and all real business logic lives in Actions. Nothing fancy layered on top unless there's a genuine reason for it.

| Layer            | Responsible for                                                | Not responsible for                            |
| ---------------- | -------------------------------------------------------------- | ---------------------------------------------- |
| **Controller**   | Receiving the request, calling an Action, returning a Resource | Business logic                                 |
| **Form Request** | HTTP validation & normalization                                | Domain logic                                   |
| **Action**       | The actual use case (e.g. `SearchParking`, `FavoriteParking`)  | Being a thin passthrough with no real behavior |
| **DTO**          | Structured input/output for operations that need it            | Wrapping one or two trivial params             |
| **API Resource** | Shaping the JSON response                                      | Domain behavior                                |

Some of the current Actions and DTOs, as examples:

```text
Actions:
  SearchParking
  ResolveParkingIdentifier
  ReportParkingAvailability
  GetParkingAvailabilityHistory
  FavoriteParking
  UnfavoriteParking
  ListFavorites

DTOs:
  LoginData
  RegisterUserData
  SearchParkingData
  ReportParkingAvailabilityData
  GetParkingAvailabilityHistoryData
  ParkingSearchResult

Resources:
  ParkingResource
  ParkingDetailResource
  FavoriteResource
  OccupancyReportResource
```

---

## Searching for parking

```http
GET /api/v1/parking
```

Parking search supports:

* parking type filtering
* availability filtering
* provider filtering
* latitude/longitude search
* radius filtering
* distance calculation
* sorting
* pagination

### Parking type

Use `type` to restrict results to one parking type:

```http
GET /api/v1/parking?type=facility
GET /api/v1/parking?type=street
```

| Parameter | Values     | Description             |
| --------- | ---------- | ----------------------- |
| `type`    | `facility` | Parking facilities only |
| `type`    | `street`   | Street parking only     |

If `type` is omitted, both parking types are searched.

---

### Availability

Filter by the current availability status:

```http
GET /api/v1/parking?filter[availability]=AVAILABLE
GET /api/v1/parking?filter[availability]=LIMITED
GET /api/v1/parking?filter[availability]=FULL
GET /api/v1/parking?filter[availability]=UNKNOWN
```

Supported values:

| Parameter              | Values      |
| ---------------------- | ----------- |
| `filter[availability]` | `UNKNOWN`   |
| `filter[availability]` | `AVAILABLE` |
| `filter[availability]` | `LIMITED`   |
| `filter[availability]` | `FULL`      |

---

### Parking provider

Filter results to a specific parking provider:

```http
GET /api/v1/parking?filter[provider_id]=1
```

| Parameter             | Type    | Description         |
| --------------------- | ------- | ------------------- |
| `filter[provider_id]` | integer | Parking provider ID |

The provider must exist in the `parking_providers` table.

---

### Location and radius

Provide latitude and longitude to perform a location-aware search:

```http
GET /api/v1/parking?latitude=25.5779199&longitude=91.8837004
```

To restrict results to a radius:

```http
GET /api/v1/parking?latitude=25.5779199&longitude=91.8837004&radius=2000
```

| Parameter   | Type    | Range           | Description             |
| ----------- | ------- | --------------- | ----------------------- |
| `latitude`  | numeric | `-90` to `90`   | Search latitude         |
| `longitude` | numeric | `-180` to `180` | Search longitude        |
| `radius`    | integer | `1`–`50000`     | Search radius in metres |

`radius` requires both `latitude` and `longitude`.

PostGIS calculates the distance between the supplied location and each parking location.

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
     results
```

---

### Sorting

Use the `sort` parameter.

Available sort fields:

| Value               | Sort order                    |
| ------------------- | ----------------------------- |
| `distance`          | Nearest first                 |
| `-distance`         | Farthest first                |
| `name`              | A–Z                           |
| `-name`             | Z–A                           |
| `capacity`          | Lowest capacity first         |
| `-capacity`         | Highest capacity first        |
| `available_spaces`  | Fewest available spaces first |
| `-available_spaces` | Most available spaces first   |

Examples:

```http
GET /api/v1/parking?sort=distance
GET /api/v1/parking?sort=-distance
GET /api/v1/parking?sort=name
GET /api/v1/parking?sort=-name
GET /api/v1/parking?sort=capacity
GET /api/v1/parking?sort=-capacity
GET /api/v1/parking?sort=available_spaces
GET /api/v1/parking?sort=-available_spaces
```

When coordinates are supplied but `sort` is omitted, the backend defaults to:

```text
distance
```

When coordinates are not supplied and `sort` is omitted, the backend defaults to:

```text
name
```

---

### Pagination

Results are paginated using:

```http
GET /api/v1/parking?page=2&per_page=20
```

| Parameter  | Type    | Default |    Limits |
| ---------- | ------- | ------: | --------: |
| `page`     | integer |     `1` |    `>= 1` |
| `per_page` | integer |    `20` | `1`–`100` |

Examples:

```http
GET /api/v1/parking?page=1&per_page=20
GET /api/v1/parking?page=2&per_page=50
```

---

### Combining filters

Filters can be combined in a single request.

Find available street parking from provider `1` within 2 km:

```http
GET /api/v1/parking?type=street&filter[availability]=AVAILABLE&filter[provider_id]=1&latitude=25.5779199&longitude=91.8837004&radius=2000
```

Find nearby facility parking with the most available spaces first:

```http
GET /api/v1/parking?type=facility&latitude=25.5779199&longitude=91.8837004&radius=2000&sort=-available_spaces
```

Find all parking sorted alphabetically:

```http
GET /api/v1/parking?sort=name
```

Find the 50 nearest parking locations on the second page:

```http
GET /api/v1/parking?latitude=25.5779199&longitude=91.8837004&sort=distance&page=2&per_page=50
```

### Search parameter summary

| Parameter              | Type    | Supported values / range                                                                                   |
| ---------------------- | ------- | ---------------------------------------------------------------------------------------------------------- |
| `type`                 | string  | `facility`, `street`                                                                                       |
| `filter[availability]` | string  | `UNKNOWN`, `AVAILABLE`, `LIMITED`, `FULL`                                                                  |
| `filter[provider_id]`  | integer | Existing provider ID                                                                                       |
| `latitude`             | numeric | `-90` to `90`                                                                                              |
| `longitude`            | numeric | `-180` to `180`                                                                                            |
| `radius`               | integer | `1`–`50000` metres; requires coordinates                                                                   |
| `sort`                 | string  | `distance`, `-distance`, `name`, `-name`, `capacity`, `-capacity`, `available_spaces`, `-available_spaces` |
| `page`                 | integer | `>= 1`                                                                                                     |
| `per_page`             | integer | `1`–`100`, default `20`                                                                                    |

---

## Availability

Every parking spot tracks its **current** best-known state:

```text
available_spaces
availability_status   # UNKNOWN | AVAILABLE | LIMITED | FULL
availability_updated_at
```

Every report that comes in also gets saved as an **immutable historical record**.

So the parking entity always shows "what's true right now", while the history is never lost.

```text
Availability report
       │
       ├── occupancy_reports record   (permanent history)
       └── current parking state      (updated in place)
```

Availability status is derived from capacity vs. current available spaces and gets validated before anything is saved.

### Reporting availability

```http
POST /api/v1/parking/facility:1/availability
```

Send:

* `available_spaces`
* `occupied_spaces`
* `confidence`

Behind the scenes:

1. Resolves the public identifier to a real record
2. Validates the report
3. Writes an immutable occupancy report
4. Updates the parking entity's current availability + timestamp
5. Returns the updated resource

The operation runs inside one transaction.

### Availability history

```http
GET /api/v1/parking/facility:1/availability/history
```

Paginated, most recent first.

### Where availability comes from

Reports can come from different sources:

```text
USER · OPERATOR · SENSOR · CAMERA · SYSTEM
```

Currently the API supports user-submitted reports. The additional source types provide a foundation for future automated integrations.

---

## Favorites

```http
POST   /api/v1/parking/facility:1/favorite
DELETE /api/v1/parking/facility:1/favorite
GET    /api/v1/favorites
```

Works with either parking type using the same public identifiers.

Favoriting the same parking resource twice is a no-op rather than creating a duplicate.

---

## Auth

Standard Sanctum token flow:

```text
Register / Login
       │
       ▼
Sanctum token
       │
       ▼
authenticated requests
```

Protected routes use Laravel's normal authenticated-user resolution.

---

## API conventions

* Everything is versioned under `/api/v1/...`
* Parking resources expose their **public** identifier, never a bare numeric ID.
* Facility and street parking use the same public identifier format throughout the API.

Example:

```json
{
    "id": "facility:1",
    "type": "facility"
}
```

---

## Testing

Built on Pest.

```bash
# full suite
sail test

# one test class
sail test --filter=ParkingIndexTest

# parking-related tests
sail test --filter=Parking
```

Coverage includes:

* authentication
* parking search
* parking details
* availability reporting
* availability history
* favorites
* public parking identifiers

**The full suite needs to pass before merging anything.**

---

## Project layout

```text
app/
├── Actions/          # application use cases
│   ├── Auth/
│   └── Parking/
├── Data/             # DTOs
│   ├── Auth/
│   └── Parking/
├── Enums/
├── Exceptions/
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Requests/Api/V1/
│   └── Resources/
├── Models/
└── Support/
    └── Parking/      # public-identity logic
```

`Support/Parking/` — not `Data/` — is where public-identity helpers such as `ParkingIdentifier` live, since they are utility/domain-support logic rather than DTOs.

---

## Guiding principles

A few things worth keeping in mind before adding new code:

* **Don't add layers you don't need yet.** No repositories, services, managers, or transformers unless there's a real, independent responsibility that justifies one.
* **Actions should do something meaningful.** If an Action is just a one-line delegation with no real behavior, it probably shouldn't be an Action.
* **HTTP stuff stays at the HTTP boundary.** Validation and normalization belong in Form Requests. Domain behavior belongs in Actions and models.
* **Identity logic goes through `ParkingIdentifier`.** Never hand-roll `instanceof` checks to determine facility vs. street.
* **Don't optimize early.** Plain Eloquent/PostGIS queries are fine until real usage data says otherwise.
* **Keep the domain concepts separate.** Parking facilities and street parking are different concepts; don't force them into a generic `Parking` model merely for abstraction.

---

## Typical feature workflow

```text
1. Define the domain behavior
2. Add/update the model
3. Add a migration
4. Add an Action
5. Add a Form Request (if HTTP validation is required)
6. Add/update an API Resource
7. Wire up Controller + Route
8. Write Pest tests
9. Run focused tests
10. Run the full suite
```

Before calling anything done:

```bash
sail test
```

must pass.

---

## What's next

| Area                            | Coming up                                                                                                                           |
| ------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| **Parking management**          | Create/update/deactivate facilities & street parking, provider & location management                                                |
| **Availability infrastructure** | Expiration, staleness detection, source-priority rules, conflict resolution, automated ingestion                                    |
| **Data quality**                | Incorrect-report flagging, corrections, moderation, duplicate/anomaly detection, source reliability                                 |
| **Geospatial**                  | Bounding-box & viewport search, spatial clustering, route-aware search                                                              |
| **Notifications**               | Availability alerts, favorite alerts, push infrastructure, preferences                                                              |
| **Production readiness**        | Rate limiting, authorization policies, standardized errors, API docs, logging, monitoring, caching, queues, scheduled jobs, backups |
| **Infrastructure**              | Kubernetes, once there is an actual deployment need                                                                                 |

---

## Why this architecture

Laravel + PostgreSQL/PostGIS + Sanctum + Actions + focused DTOs + Form Requests + API Resources + Pest — deliberately **not** a heavily layered architecture.

The goal is a backend that's easy to understand, easy to test, and cheap to run, while still leaving room to grow into real-time availability, automated data sources, and richer geospatial features when the time comes.
