# 🅿️ Parking Tracker — Documentation & API Reference

A modern Laravel backend for city parking discovery, geospatial search, real-time availability tracking, and multi-tenant operator management.

---

## Table of Contents

- [Overview & Capabilities](#overview--capabilities)
- [Architecture & Design Principles](#architecture--design-principles)
- [Domain Concepts](#domain-concepts)
  - [Parking Identity](#parking-identity)
  - [Availability Lifecycle & Invariants](#availability-lifecycle--invariants)
  - [PostgreSQL Database Guarantees](#postgresql-database-guarantees)
  - [Availability Freshness & Stale Tracking](#availability-freshness--stale-tracking)
- [Multi-Tenancy & RBAC](#multi-tenancy--rbac)
  - [Tenant Architecture](#tenant-architecture)
  - [Roles & Permissions Matrix](#roles--permissions-matrix)
  - [Model Authorization Policies](#model-authorization-policies)
- [Filament 5 Administrative Panels](#filament-5-administrative-panels)
  - [Platform Admin Panel (`/admin`)](#platform-admin-panel-admin)
  - [Provider Operator Panel (`/provider`)](#provider-operator-panel-provider)
- [REST API Reference](#rest-api-reference)
  - [Conventions & Base URL](#conventions--base-url)
  - [Authentication & Headers](#authentication--headers)
  - [Error Format & Status Codes](#error-format--status-codes)
  - [Rate Limiting](#rate-limiting)
  - [Authentication Endpoints](#authentication-endpoints)
    - [`POST /api/v1/auth/register`](#post-apiv1authregister)
    - [`POST /api/v1/auth/login`](#post-apiv1authlogin)
    - [`GET /api/v1/auth/me`](#get-apiv1authme)
    - [`POST /api/v1/auth/logout`](#post-apiv1authlogout)
  - [Parking Discovery Endpoints](#parking-discovery-endpoints)
    - [`GET /api/v1/parking`](#get-apiv1parking)
    - [`GET /api/v1/parking/nearby`](#get-apiv1parkingnearby)
    - [`GET /api/v1/parking/{parking}`](#get-apiv1parkingparking)
  - [Availability & Reporting Endpoints](#availability--reporting-endpoints)
    - [`POST /api/v1/parking/{parking}/availability`](#post-apiv1parkingparkingavailability)
    - [`GET /api/v1/parking/{parking}/availability/history`](#get-apiv1parkingparkingavailabilityhistory)
  - [Favorites Endpoints](#favorites-endpoints)
    - [`GET /api/v1/favorites`](#get-apiv1favorites)
    - [`POST /api/v1/favorites/{parking}`](#post-apiv1favoritesparking)
    - [`DELETE /api/v1/favorites/{parking}`](#delete-apiv1favoritesparking)
- [Geospatial & Magellan](#geospatial--magellan)
- [Quick Start & Local Development](#quick-start--local-development)
  - [Prerequisites](#prerequisites)
  - [Setup Instructions](#setup-instructions)
  - [Seeded Development Accounts](#seeded-development-accounts)
  - [Useful Commands](#useful-commands)
- [Testing](#testing)
- [Project Structure](#project-structure)
- [Roadmap](#roadmap)

---

## Overview & Capabilities

Parking Tracker is built to handle the complexities of modern city parking infrastructure:

- **Dual parking models**: Distinguishes between off-street managed facilities (garages, multi-level lots) and on-street parking segments.
- **Geospatial discovery**: PostGIS-backed proximity queries, spherical distance calculations, and radius bounding via Clickbar Magellan.
- **Immutable occupancy auditing**: Every availability report creates an immutable observation record, while the current parking state is atomically updated with database row locking.
- **Database-enforced integrity**: PostgreSQL triggers and check constraints ensure audit records can never be altered or deleted, and capacity counts remain mathematically valid.
- **Freshness monitoring**: Automated tracking of stale availability with configurable thresholds and dedicated administrative oversight.
- **Multi-tenant RBAC**: Team-based permissions using Spatie Laravel-Permission, isolating provider operators while granting platform admins global visibility.
- **Two administrative panels**: Filament 5 Admin panel (with live availability monitoring dashboard) and Provider panel (with self-service tenant onboarding).
- **Full REST API**: Versioned JSON API with Sanctum bearer token authentication, request throttling, and Scribe interactive documentation.

**Technology Stack:** PHP 8.3+ · Laravel 13 · PostgreSQL 16 with PostGIS · Filament 5 · Spatie Permission (Teams) · Clickbar Magellan · Laravel Sanctum · Pest 5 · Docker / Laravel Sail.

---

## Architecture & Design Principles

The application adheres to an action-oriented architecture with strict boundary separation:

```text
HTTP Request / Filament Form
 │
 ├── Form Request / Input Schema (validation, normalization, type-casting)
 │
 ├── Controller / Filament Resource Page (HTTP coordination)
 │
 ├── Gate & Model Policy (authorization: platform admin vs tenant permissions)
 │
 ├── DTO (strongly typed cross-boundary input)
 │
 └── Action / Query Builder
        │
        ├── Domain & Application Behavior (transactions, row locking)
        └── Eloquent + PostgreSQL / PostGIS
                │
                └── API Resource (consistent JSON representations)
```

### Action pipelines

1. **Availability Reporting Pipeline:**
   ```text
   ReportParkingAvailability
           │
           ├── ResolveParkingIdentifier (locks target record with lockForUpdate())
           │
           ├── AvailabilityReportRules (validates capacity invariants)
           │
           ├── RecordOccupancyObservation (creates immutable OccupancyReport)
           │
           └── ApplyOccupancyToParking (updates current state & derives status)
   ```

2. **Search Pipeline:**
   ```text
   SearchParking
           │
           ├── ParkingSearchQueryBuilder (constructs subqueries, spatial distances, filters, sort)
           │
           └── ParkingSearchResultHydrator (hydrates raw result rows into typed models with relations)
   ```

---

## Domain Concepts

### Parking Identity

Because IDs in `parking_facilities` and `street_parkings` are only unique within their respective tables, public APIs and cross-boundary actions use a unified, typed identifier format:

```text
facility:{id}   --> e.g. facility:1
street:{id}     --> e.g. street:42
```

- **Formatting & Resolution:** Centralized in [`ParkingIdentifier`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Support/Parking/ParkingIdentifier.php), [`ParkingIdentifierParser`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Support/Parking/ParkingIdentifierParser.php), and [`ResolveParkingIdentifier`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Actions/Parking/ResolveParkingIdentifier.php).
- **Validation:** Strict regex validation (`/^(facility|street):([1-9][0-9]*)$/`). Invalid identifiers immediately return `404 Not Found`.

### Availability Lifecycle & Invariants

The application separates **point-in-time observations** from **current operational state**:

```text
Occupancy Report (Immutable Observation)
 ├── parking_facility_id / street_parking_id
 ├── occupied_spaces (integer >= 0)
 ├── available_spaces (integer >= 0)
 ├── source (USER, OPERATOR, SENSOR, CAMERA, SYSTEM)
 └── created_at / reported_at
       │
       ▼ (Atomic update via database transaction)
Current Parking State (Mutable Operational State)
 ├── available_spaces
 ├── availability_status (UNKNOWN, FULL, LIMITED, AVAILABLE)
 ├── availability_updated_at
 ├── availability_source
 └── availability_report_id (FK to latest observation)
```

#### Domain Validation Rules

Before an observation is recorded, [`AvailabilityReportRules`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Support/Parking/AvailabilityReportRules.php) validates:
1. Parking capacity must be established before availability can be reported.
2. Capacity cannot be negative.
3. `available_spaces` must be between `0` and `capacity`.
4. `occupied_spaces` must be between `0` and `capacity`.
5. `available_spaces + occupied_spaces` cannot exceed `capacity`.

#### Availability Status Calculation

Current status is computed deterministically:
- `UNKNOWN`: Capacity is not set or availability has never been reported.
- `FULL`: `available_spaces === 0`.
- `LIMITED`: Remaining spaces are at or below 20% of capacity (`available_spaces / capacity <= 0.20`).
- `AVAILABLE`: Remaining spaces exceed 20% of capacity.

### PostgreSQL Database Guarantees

In addition to PHP validation, PostgreSQL enforces strict data integrity rules at the storage engine level:

1. **Counts Required Check Constraint:**
   `occupancy_reports_counts_required_check` ensures that `occupied_spaces` and `available_spaces` are never `NULL`.
2. **PostgreSQL Immutability Trigger:**
   A plpgsql function and trigger (`prevent_occupancy_report_mutation()`) executes on `BEFORE UPDATE OR DELETE ON occupancy_reports`, raising an exception:
   ```sql
   RAISE EXCEPTION 'Occupancy reports are immutable observations';
   ```
   Direct SQL updates or deletions against historical observations are rejected.
3. **Foreign Key Deletion Restriction:**
   Foreign keys on `occupancy_reports` use `restrictOnDelete()`. A facility or street parking record cannot be deleted if historical occupancy reports reference it, preserving complete audit trails.

### Availability Freshness & Stale Tracking

Availability data degrades over time. Parking Tracker includes built-in staleness detection:

- **Configuration:** `config/parking.php` defines `availability_stale_after_minutes` (default: `60` minutes, configurable via `PARKING_AVAILABILITY_STALE_AFTER_MINUTES`).
- **[`HasStaleAvailability`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Models/Concerns/HasStaleAvailability.php) Trait:**
  - `scopeStaleAvailability()`: Records whose last update is older than the freshness cutoff.
  - `scopeStaleOrNeverReportedAvailability()`: Records that are either older than the cutoff or have never received an update.
- **Monitoring Integration:** Administrative widgets actively query these scopes to surface unresponsive sensors, missing reports, or unattended lots.

---

## Multi-Tenancy & RBAC

The platform provides multi-tenant role-based access control combining Filament tenancy, Spatie Permission teams, and Laravel model policies.

### Tenant Architecture

- **Tenant Model:** [`ParkingProvider`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Models/ParkingProvider.php) acts as the tenant for operator-facing actions.
- **Spatie Teams:** Configured with `teams = true` and `team_foreign_key = 'parking_provider_id'`.
- **Tenant Middleware:** [`SyncSpatiePermissionsWithFilamentTenants`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Http/Middleware/SyncSpatiePermissionsWithFilamentTenants.php) synchronizes Spatie's active team (`setPermissionsTeamId(Filament::getTenant()?->getKey())`) on every provider panel request.
- **Global Role:** Platform administrators hold the `platform-admin` role globally (`team_id = null`), granting unrestricted access across all tenants and panels.

### Roles & Permissions Matrix

Provider team members hold one of four roles within their provider tenant:

| Permission | Owner | Admin | Manager | Viewer |
| :--- | :---: | :---: | :---: | :---: |
| `facility.view` / `area.view` / `street_parking.view` | ✅ | ✅ | ✅ | ✅ |
| `facility.create` / `area.create` / `street_parking.create` | ✅ | ✅ | ✅ | ❌ |
| `facility.update` / `area.update` / `street_parking.update` | ✅ | ✅ | ✅ | ❌ |
| `facility.delete` / `area.delete` / `street_parking.delete` | ✅ | ✅ | ❌ | ❌ |
| `occupancy_report.view` | ✅ | ✅ | ✅ | ✅ |
| `occupancy_report.create` | ✅ | ✅ | ✅ | ❌ |
| `occupancy_report.delete` | ✅ | ✅ | ❌ | ❌ |
| `provider.view` / `membership.view` | ✅ | ✅ | ✅ | ✅ |
| `provider.update` | ✅ | ✅ | ❌ | ❌ |
| `membership.invite` / `membership.update` | ✅ | ✅ | ❌ | ❌ |
| `membership.remove` | ✅ | ❌ | ❌ | ❌ |

> [!NOTE]
> `occupancy_report.update` is deliberately omitted. Observations cannot be updated by any role.

### Model Authorization Policies

All domain resources are protected by explicit policies:
- [`ParkingFacilityPolicy`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Policies/ParkingFacilityPolicy.php)
- [`StreetParkingPolicy`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Policies/StreetParkingPolicy.php)
- [`ParkingAreaPolicy`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Policies/ParkingAreaPolicy.php)
- [`OccupancyReportPolicy`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Policies/OccupancyReportPolicy.php)
- [`ParkingProviderPolicy`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Policies/ParkingProviderPolicy.php)
- [`ProviderMembershipPolicy`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Policies/ProviderMembershipPolicy.php)

Each policy grants immediate authorization if the user has the `platform-admin` role, otherwise verifying team permissions against the provider via [`ProviderAuthorization`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Services/Rbac/ProviderAuthorization.php).

---

## Filament 5 Administrative Panels

### Platform Admin Panel (`/admin`)

Intended for municipal administrators and platform operators:
- **Availability Monitoring Dashboard:** Live monitoring page (`AvailabilityMonitoring`) with the [`AvailabilitySummary`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Filament/Admin/Widgets/AvailabilitySummary.php) widget (counts by status + stale indicator), [`FacilityAvailabilityTable`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Filament/Admin/Widgets/FacilityAvailabilityTable.php), and [`StreetParkingAvailabilityTable`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/app/Filament/Admin/Widgets/StreetParkingAvailabilityTable.php).
- **Provider Management:** Complete CRUD and status activation for parking providers.
- **Audit Logs:** View-only access to historical occupancy reports.
- **Attention Queue:** Highlights facilities with zero spaces, missing capacity, or stale reporting.

### Provider Operator Panel (`/provider`)

A self-contained portal for individual parking operators:
- **Tenancy Onboarding:** Providers register (`RegisterProvider`) and manage their profile (`EditProviderProfile`).
- **Scoped Management:** Direct CRUD for facilities, areas, and street parking belonging to the active provider tenant.
- **Inline Availability Modal:** Record occupancy observations directly from table rows via `ReportAvailabilityAction`.
- **Interactive Map Picker:** Custom Leaflet / MapLibre integration (`GeometryPicker` & `GeometryEntry`) for drawing point markers and line strings.

---

## REST API Reference

### Conventions & Base URL

- **Base URL:** `/api/v1` (e.g. `http://localhost/api/v1`)
- **Default Headers:**
  ```http
  Accept: application/json
  Content-Type: application/json
  ```
- **Responses:** All responses return standard JSON. Resource collections include `data`, `links`, and `meta` keys.

### Authentication & Headers

Protected endpoints require a Laravel Sanctum Bearer token:

```http
Authorization: Bearer <your-access-token>
```

Tokens are obtained via `/api/v1/auth/register` or `/api/v1/auth/login`.

### Error Format & Status Codes

The API uses standard HTTP status codes and structured JSON errors:

| Status Code | Description | Typical Cause |
| :--- | :--- | :--- |
| `200 OK` | Request succeeded | Successful GET, PUT, or POST action |
| `201 Created` | Resource created | Registration, initial favorite addition |
| `204 No Content` | Action succeeded with no body | Resource deletion (e.g. unfavorite) |
| `401 Unauthorized` | Authentication missing or invalid | Missing or expired Sanctum Bearer token |
| `403 Forbidden` | Insufficient authorization | Access denied by model policy or RBAC |
| `404 Not Found` | Resource does not exist | Invalid parking identifier or nonexistent ID |
| `422 Unprocessable` | Validation or invariant failure | Invalid input fields, capacity rules exceeded |
| `429 Too Many Requests`| Rate limit exceeded | Reporting availability more than 10 times/minute |

#### Standard Validation / Invariant Error Format (`422 Unprocessable Content`)

```json
{
  "message": "Occupied and available spaces cannot exceed parking capacity.",
  "errors": {
    "available_spaces": [
      "Occupied and available spaces cannot exceed parking capacity."
    ]
  }
}
```

#### Standard Authentication Error Format (`401 Unauthorized`)

```json
{
  "message": "Unauthenticated."
}
```

### Rate Limiting

- Standard endpoints inherit default application rate limits.
- **Availability reporting** (`POST /api/v1/parking/{parking}/availability`) is throttled to **10 requests per minute** per authenticated user (`throttle:10,1`). Exceeding this returns `429 Too Many Requests`.

---

### Authentication Endpoints

#### `POST /api/v1/auth/register`

Register a new user account and obtain an initial access token.

- **Access:** Public
- **Headers:** `Content-Type: application/json`, `Accept: application/json`

**Request Body:**

| Field | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `name` | string | Yes | Full name (max: 255) |
| `email` | string | Yes | Valid unique email address (max: 255) |
| `password` | string | Yes | Account password (min: 8 characters) |
| `password_confirmation` | string | Yes | Must match `password` |
| `device_name` | string | Yes | Descriptive device or client identifier (max: 100) |

**Example Request:**

```bash
curl -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "device_name": "iPhone 15 Pro"
  }'
```

**Response (`201 Created`):**

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "Jane Doe",
      "email": "jane@example.com"
    },
    "token": "1|q6w4e8r9t1y2u3i4o5p6a7s8d9f0..."
  }
}
```

---

#### `POST /api/v1/auth/login`

Authenticate with email and password to receive a Sanctum bearer token.

- **Access:** Public
- **Headers:** `Content-Type: application/json`, `Accept: application/json`

**Request Body:**

| Field | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `email` | string | Yes | Registered user email |
| `password` | string | Yes | Account password |
| `device_name` | string | Yes | Descriptive client identifier (max: 100) |

**Example Request:**

```bash
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "jane@example.com",
    "password": "password123",
    "device_name": "MacBook Chrome"
  }'
```

**Response (`200 OK`):**

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "Jane Doe",
      "email": "jane@example.com"
    },
    "token": "2|u1i2o3p4a5s6d7f8g9h0j1k2l3..."
  }
}
```

**Error Response (`401 Unauthorized`):**

```json
{
  "message": "Invalid credentials."
}
```

---

#### `GET /api/v1/auth/me`

Retrieve the profile of the currently authenticated user.

- **Access:** Authenticated (`auth:sanctum`)
- **Headers:** `Authorization: Bearer <token>`, `Accept: application/json`

**Example Request:**

```bash
curl -X GET http://localhost/api/v1/auth/me \
  -H "Authorization: Bearer 2|u1i2o3p4a5s6d7f8g9h0j1k2l3..." \
  -H "Accept: application/json"
```

**Response (`200 OK`):**

```json
{
  "data": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com"
  }
}
```

---

#### `POST /api/v1/auth/logout`

Revoke the bearer token used for the current request.

- **Access:** Authenticated (`auth:sanctum`)
- **Headers:** `Authorization: Bearer <token>`, `Accept: application/json`

**Example Request:**

```bash
curl -X POST http://localhost/api/v1/auth/logout \
  -H "Authorization: Bearer 2|u1i2o3p4a5s6d7f8g9h0j1k2l3..." \
  -H "Accept: application/json"
```

**Response (`200 OK`):**

```json
{
  "message": "Logged out successfully."
}
```

---

### Parking Discovery Endpoints

#### `GET /api/v1/parking`

Discover parking facilities and street parking segments with comprehensive filtering, spatial search, sorting, and pagination.

- **Access:** Public
- **Headers:** `Accept: application/json`

**Query Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `type` | string | No | Filter by parking type: `facility` or `street` |
| `filter[availability]` | string | No | Filter by status: `AVAILABLE`, `LIMITED`, `FULL`, `UNKNOWN` |
| `filter[provider_id]` | integer | No | Filter by provider ID (must exist in `parking_providers`) |
| `latitude` | float | No | WGS84 latitude (`-90` to `90`) |
| `longitude` | float | No | WGS84 longitude (`-180` to `180`) |
| `radius` | integer | No | Bounding radius in meters (`1` to `50000`). *Requires latitude and longitude.* |
| `sort` | string | No | Field to sort by: `distance`, `-distance`, `name`, `-name`, `capacity`, `-capacity`, `available_spaces`, `-available_spaces` |
| `page` | integer | No | Page number (min: `1`, default: `1`) |
| `per_page` | integer | No | Results per page (min: `1`, max: `100`, default: `20`) |

**Example Request:**

```bash
curl -X GET "http://localhost/api/v1/parking?type=facility&filter%5Bavailability%5D=AVAILABLE&latitude=25.5788&longitude=91.8821&radius=2500&sort=distance" \
  -H "Accept: application/json"
```

**Response (`200 OK`):**

```json
{
  "data": [
    {
      "id": "facility:1",
      "type": "facility",
      "name": "Police Bazar Parking Complex",
      "distance": {
        "meters": 142.3,
        "kilometers": 0.14
      },
      "provider": {
        "id": 1,
        "name": "Urban Mobility Meghalaya",
        "type": "MUNICIPAL"
      },
      "location": {
        "address_line1": "GS Road, Police Bazar",
        "locality": "Shillong",
        "administrative_area": "Meghalaya",
        "postal_code": "793001",
        "country_code": "IN",
        "coordinates": {
          "latitude": 25.5788,
          "longitude": 91.8821
        }
      },
      "capacity": 50,
      "availability": {
        "status": "AVAILABLE",
        "available_spaces": 18,
        "updated_at": "2026-10-23T05:15:00.000000Z"
      }
    }
  ],
  "links": {
    "first": "http://localhost/api/v1/parking?page=1",
    "last": "http://localhost/api/v1/parking?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 20,
    "to": 1,
    "total": 1
  }
}
```

---

#### `GET /api/v1/parking/nearby`

A dedicated shortcut for proximity-based parking search, returning locations ordered by distance from given coordinates.

- **Access:** Public
- **Headers:** `Accept: application/json`

**Query Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `latitude` | float | **Yes** | Center point latitude (`-90` to `90`) |
| `longitude` | float | **Yes** | Center point longitude (`-180` to `180`) |
| `radius` | integer | No | Search radius in meters (`1` to `50000`, default: `2000`) |
| `limit` | integer | No | Maximum number of results (`1` to `100`, default: `50`) |

**Example Request:**

```bash
curl -X GET "http://localhost/api/v1/parking/nearby?latitude=25.5779199&longitude=91.8837004&radius=1500&limit=10" \
  -H "Accept: application/json"
```

**Response (`200 OK`):**

```json
{
  "data": [
    {
      "id": "facility:1",
      "type": "facility",
      "name": "Police Bazar Parking Complex",
      "distance": {
        "meters": 188.4,
        "kilometers": 0.19
      },
      "provider": {
        "id": 1,
        "name": "Urban Mobility Meghalaya",
        "type": "MUNICIPAL"
      },
      "location": {
        "address_line1": "GS Road, Police Bazar",
        "locality": "Shillong",
        "administrative_area": "Meghalaya",
        "postal_code": "793001",
        "country_code": "IN",
        "coordinates": {
          "latitude": 25.5788,
          "longitude": 91.8821
        }
      },
      "capacity": 50,
      "availability": {
        "status": "AVAILABLE",
        "available_spaces": 18,
        "updated_at": "2026-10-23T05:15:00.000000Z"
      }
    },
    {
      "id": "street:3",
      "type": "street",
      "name": "Kachari Road On-Street Parking",
      "distance": {
        "meters": 412.1,
        "kilometers": 0.41
      },
      "provider": {
        "id": 1,
        "name": "Urban Mobility Meghalaya",
        "type": "MUNICIPAL"
      },
      "location": {
        "address_line1": "Kachari Road",
        "locality": "Shillong",
        "administrative_area": "Meghalaya",
        "postal_code": "793001",
        "country_code": "IN",
        "coordinates": {
          "latitude": 25.5768,
          "longitude": 91.8826
        }
      },
      "capacity": 20,
      "availability": {
        "status": "LIMITED",
        "available_spaces": 3,
        "updated_at": "2026-10-23T05:20:00.000000Z"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 2,
    "last_page": 1
  }
}
```

---

#### `GET /api/v1/parking/{parking}`

Retrieve detailed information, coordinates, provider details, and live availability for a single parking location.

- **Access:** Public
- **Headers:** `Accept: application/json`

**Path Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `parking` | string | **Yes** | Typed parking identifier: `facility:{id}` or `street:{id}` |

**Example Request:**

```bash
curl -X GET http://localhost/api/v1/parking/facility:1 \
  -H "Accept: application/json"
```

**Response (`200 OK`):**

```json
{
  "data": {
    "id": "facility:1",
    "type": "facility",
    "name": "Police Bazar Parking Complex",
    "provider": {
      "id": 1,
      "name": "Urban Mobility Meghalaya",
      "type": "MUNICIPAL"
    },
    "location": {
      "address_line1": "GS Road, Police Bazar",
      "locality": "Shillong",
      "administrative_area": "Meghalaya",
      "postal_code": "793001",
      "country_code": "IN",
      "coordinates": {
        "latitude": 25.5788,
        "longitude": 91.8821
      }
    },
    "capacity": 50,
    "availability": {
      "status": "AVAILABLE",
      "available_spaces": 18,
      "updated_at": "2026-10-23T05:15:00.000000Z"
    }
  }
}
```

**Error Response (`404 Not Found`):**

```json
{
  "message": "No query results for model [App\\Models\\ParkingFacility] 999999"
}
```

---

### Availability & Reporting Endpoints

#### `POST /api/v1/parking/{parking}/availability`

Submit an occupancy observation for a parking facility or street parking segment.

- **Access:** Authenticated (`auth:sanctum`)
- **Rate Limit:** 10 requests / minute (`throttle:10,1`)
- **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`, `Accept: application/json`

**Path Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `parking` | string | **Yes** | Typed parking identifier: `facility:{id}` or `street:{id}` |

**Request Body:**

| Field | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `available_spaces` | integer | **Yes** | Number of open spaces (`min: 0`) |
| `occupied_spaces` | integer | No | Number of taken spaces (`min: 0`). *Derived as `capacity - available_spaces` if omitted.* |

**Validation & Invariants:**
- `capacity` must be configured on the parking location.
- `available_spaces` cannot exceed `capacity`.
- `occupied_spaces` cannot exceed `capacity`.
- `available_spaces + occupied_spaces` cannot exceed `capacity`.

**Example Request:**

```bash
curl -X POST http://localhost/api/v1/parking/facility:1/availability \
  -H "Authorization: Bearer 2|u1i2o3p4a5s6d7f8g9h0j1k2l3..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "available_spaces": 15,
    "occupied_spaces": 35
  }'
```

**Response (`200 OK`):**

```json
{
  "data": {
    "id": "facility:1",
    "type": "facility",
    "name": "Police Bazar Parking Complex",
    "provider": {
      "id": 1,
      "name": "Urban Mobility Meghalaya",
      "type": "MUNICIPAL"
    },
    "location": {
      "address_line1": "GS Road, Police Bazar",
      "locality": "Shillong",
      "administrative_area": "Meghalaya",
      "postal_code": "793001",
      "country_code": "IN",
      "coordinates": {
        "latitude": 25.5788,
        "longitude": 91.8821
      }
    },
    "capacity": 50,
    "availability": {
      "status": "AVAILABLE",
      "available_spaces": 15,
      "updated_at": "2026-10-23T05:25:00.000000Z"
    }
  },
  "meta": {
    "report_id": 481,
    "report_accepted": true
  }
}
```

**Error Responses:**

*Invalid capacity invariant (`422 Unprocessable Content`):*
```json
{
  "message": "Available spaces must be between zero and capacity.",
  "errors": {
    "available_spaces": [
      "Available spaces must be between zero and capacity."
    ]
  }
}
```

*Rate limit exceeded (`429 Too Many Requests`):*
```json
{
  "message": "Too Many Attempts."
}
```

---

#### `GET /api/v1/parking/{parking}/availability/history`

Retrieve the immutable historical occupancy observation log for a parking location, ordered newest first.

- **Access:** Public
- **Headers:** `Accept: application/json`

**Path Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `parking` | string | **Yes** | Typed parking identifier: `facility:{id}` or `street:{id}` |

**Query Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `page` | integer | No | Page number (min: `1`, default: `1`) |
| `per_page` | integer | No | Results per page (min: `1`, max: `100`, default: `20`) |

**Example Request:**

```bash
curl -X GET "http://localhost/api/v1/parking/facility:1/availability/history?per_page=5" \
  -H "Accept: application/json"
```

**Response (`200 OK`):**

```json
{
  "data": [
    {
      "available_spaces": 15,
      "occupied_spaces": 35,
      "source": "USER",
      "reported_at": "2026-10-23T05:25:00.000000Z"
    },
    {
      "available_spaces": 18,
      "occupied_spaces": 32,
      "source": "OPERATOR",
      "reported_at": "2026-10-23T05:15:00.000000Z"
    },
    {
      "available_spaces": 25,
      "occupied_spaces": 25,
      "source": "SYSTEM",
      "reported_at": "2026-10-23T04:00:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost/api/v1/parking/facility:1/availability/history?page=1",
    "last": "http://localhost/api/v1/parking/facility:1/availability/history?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 5,
    "to": 3,
    "total": 3
  }
}
```

---

### Favorites Endpoints

#### `GET /api/v1/favorites`

List all parking facilities and street parking saved as favorites by the authenticated user.

- **Access:** Authenticated (`auth:sanctum`)
- **Headers:** `Authorization: Bearer <token>`, `Accept: application/json`

**Query Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `page` | integer | No | Page number (min: `1`, default: `1`) |
| `per_page` | integer | No | Results per page (min: `1`, max: `100`, default: `20`) |

**Example Request:**

```bash
curl -X GET http://localhost/api/v1/favorites \
  -H "Authorization: Bearer 2|u1i2o3p4a5s6d7f8g9h0j1k2l3..." \
  -H "Accept: application/json"
```

**Response (`200 OK`):**

```json
{
  "data": [
    {
      "id": 1,
      "parking": {
        "id": "facility:1",
        "type": "facility",
        "name": "Police Bazar Parking Complex"
      },
      "created_at": "2026-10-23T04:30:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost/api/v1/favorites?page=1",
    "last": "http://localhost/api/v1/favorites?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 20,
    "to": 1,
    "total": 1
  }
}
```

---

#### `POST /api/v1/favorites/{parking}`

Add a parking facility or street parking segment to the authenticated user's favorites. This endpoint is idempotent: favoriting an existing favorite returns `200 OK` instead of an error.

- **Access:** Authenticated (`auth:sanctum`)
- **Headers:** `Authorization: Bearer <token>`, `Accept: application/json`

**Path Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `parking` | string | **Yes** | Typed parking identifier: `facility:{id}` or `street:{id}` |

**Example Request:**

```bash
curl -X POST http://localhost/api/v1/favorites/facility:1 \
  -H "Authorization: Bearer 2|u1i2o3p4a5s6d7f8g9h0j1k2l3..." \
  -H "Accept: application/json"
```

**Response (`201 Created` or `200 OK` if already exists):**

```json
{
  "data": {
    "id": 1,
    "parking": {
      "id": "facility:1",
      "type": "facility",
      "name": "Police Bazar Parking Complex"
    },
    "created_at": "2026-10-23T04:30:00.000000Z"
  }
}
```

---

#### `DELETE /api/v1/favorites/{parking}`

Remove a parking location from the authenticated user's favorites.

- **Access:** Authenticated (`auth:sanctum`)
- **Headers:** `Authorization: Bearer <token>`, `Accept: application/json`

**Path Parameters:**

| Parameter | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `parking` | string | **Yes** | Typed parking identifier: `facility:{id}` or `street:{id}` |

**Example Request:**

```bash
curl -X DELETE http://localhost/api/v1/favorites/facility:1 \
  -H "Authorization: Bearer 2|u1i2o3p4a5s6d7f8g9h0j1k2l3..." \
  -H "Accept: application/json"
```

**Response (`204 No Content`):**

*(Empty response body)*

---

## Geospatial & Magellan

The spatial infrastructure is backed by **PostgreSQL 16 and PostGIS**, integrated via **Clickbar Magellan**:

- **Geometry Primitives:**
  - `Point`: Used for facility coordinates and address locations (`Location::class`).
  - `LineString`: Used for street parking curb segments (`StreetParking::class`).
- **Spatial Calculations:**
  - Distance between user coordinates and parking points is computed using PostGIS spherical algorithms (`ST_DistanceSphere` / `ST_DWithin`).
  - Street parking distance is calculated to the closest point along the street's `LineString`.
- **Seeded Shillong Dataset:** The development migrations provide realistic, geographically accurate coordinates centered on Shillong, Meghalaya (India):
  - Police Bazar Multi-Level Car Park: `25.5788° N, 91.8821° E`
  - Kachari Road Street Parking: `25.5768° N, 91.8826° E`

---

## Quick Start & Local Development

### Prerequisites

- [Docker Desktop](https://www.docker.com/) or Docker Engine + Docker Compose
- Git
- PHP 8.3+ and Composer 2 (for local host tooling)

### Setup Instructions

1. **Clone the repository:**
   ```bash
   git clone https://github.com/on3ss/parking-tracker-backend.git
   cd parking-tracker-backend
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Boot Laravel Sail:**
   ```bash
   ./vendor/bin/sail up -d
   ```
   *(Recommended shell alias: `alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'`)*

5. **Run migrations and seed development data:**
   ```bash
   sail artisan migrate:fresh --seed
   ```

6. **Generate API documentation (Scribe):**
   ```bash
   sail artisan scribe:generate
   ```
   Interactive documentation will be available at `http://localhost/docs`.

---

### Seeded Development Accounts

The seeder ([`ShillongParkingSeeder`](file:///home/oness/projects/parking-tracker/parking-tracker-backend/database/seeders/ShillongParkingSeeder.php)) initializes realistic data:

| Account Type | Email | Password | Role / Access |
| :--- | :--- | :--- | :--- |
| **Provider Owner** | `owner@example.com` | `password` | Owner of the primary seeded provider |

The seeder also provisions:
- 8 distinct parking providers with initialized team roles (`Owner`, `Admin`, `Manager`, `Viewer`).
- 25 users assigned randomized memberships and roles across the providers.
- Parking facilities with individual parking areas (e.g. Basements, Ground Level, Rooftop).
- Georeferenced street parking segments with PostGIS line strings.
- Historical occupancy reports for testing availability trends and history endpoints.

---

### Useful Commands

```bash
# View running container logs
sail logs -f

# Run the full test suite
sail test

# Run code style fixer (Laravel Pint)
sail bin pint

# Open PostgreSQL interactive shell
sail psql

# Open Laravel Tinker shell
sail artisan tinker

# Stop all Sail containers
sail down
```

---

## Testing

Parking Tracker includes over **400 tests** written with **Pest 5**, covering every layer of the application:

```text
tests/
├── Feature/
│   ├── Api/V1/
│   │   ├── Auth/              # Register, login, me, logout endpoints
│   │   └── Parking/           # Discovery, nearby, availability, favorites endpoints
│   ├── Filament/
│   │   ├── Admin/             # Admin panel dashboards & availability monitoring
│   │   ├── ParkingFacilities/ # Facility CRUD and areas relation manager
│   │   ├── StreetParkings/    # Street parking CRUD
│   │   ├── Tenancy/           # Provider registration & profile editing
│   │   └── Widgets/           # Freshness tables, attention queues, summary counters
│   ├── Policies/              # Facility, area, report, provider authorization rules
│   └── Services/Rbac/         # Membership service & team authorization checks
│
├── Integration/
│   ├── Actions/               # Availability reporting, search query builder & hydrator
│   ├── Models/                # Stale availability Eloquent scopes
│   ├── Persistence/           # PostgreSQL triggers, check constraints & delete restrictions
│   └── PostGIS/               # Spatial geometry persistence & distance queries
│
└── Unit/
    ├── Enums/                 # AvailabilityStatus business calculations
    ├── Services/Rbac/         # TeamRoleProvisioner idempotency
    └── Support/               # Availability rules, identifier parser, GeoJSON helpers
```

### Running Tests

```bash
# Run the complete test suite
sail test

# Run only Unit tests
sail test --testsuite=Unit

# Run API feature tests
sail test tests/Feature/Api

# Run authorization policy tests
sail test tests/Feature/Policies

# Run persistence and PostgreSQL trigger tests
sail test tests/Integration/Persistence

# Filter tests by name
sail test --filter=ReportParkingAvailability
```

---

## Project Structure

```text
app/
├── Actions/
│   ├── Auth/
│   │   ├── AuthenticateUser.php
│   │   ├── GetAuthenticatedUser.php
│   │   ├── LogoutUser.php
│   │   └── RegisterUser.php
│   └── Parking/
│       ├── ApplyOccupancyToParking.php
│       ├── FavoriteParking.php
│       ├── GetParkingAvailabilityHistory.php
│       ├── ListFavorites.php
│       ├── ParkingSearchQueryBuilder.php
│       ├── ParkingSearchResultHydrator.php
│       ├── RecordOccupancyObservation.php
│       ├── ReportParkingAvailability.php
│       ├── ResolveParkingIdentifier.php
│       ├── SearchParking.php
│       ├── UnfavoriteParking.php
│       └── UpdateStreetParking.php
├── Data/
├── Enums/
│   ├── AvailabilityStatus.php
│   └── ParkingSource.php
├── Exceptions/
│   └── Parking/
│       └── InvalidParkingAvailability.php
├── Filament/
│   ├── Actions/Parking/ReportAvailabilityAction.php
│   ├── Admin/Pages/AvailabilityMonitoring.php
│   ├── Admin/Resources/
│   ├── Admin/Widgets/
│   ├── Provider/Pages/Tenancy/
│   ├── Provider/Widgets/
│   └── Resources/
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Middleware/
│   │   └── SyncSpatiePermissionsWithFilamentTenants.php
│   ├── Requests/
│   └── Resources/
├── Models/
│   ├── Concerns/HasStaleAvailability.php
│   ├── Location.php
│   ├── OccupancyReport.php
│   ├── ParkingArea.php
│   ├── ParkingFacility.php
│   ├── ParkingProvider.php
│   ├── ProviderMembership.php
│   ├── StreetParking.php
│   └── User.php
├── Policies/
│   ├── OccupancyReportPolicy.php
│   ├── ParkingAreaPolicy.php
│   ├── ParkingFacilityPolicy.php
│   ├── ParkingProviderPolicy.php
│   ├── ProviderMembershipPolicy.php
│   └── StreetParkingPolicy.php
├── Services/Rbac/
│   ├── PlatformAuthorization.php
│   ├── ProviderAuthorization.php
│   ├── ProviderMembershipLookup.php
│   ├── ProviderMembershipService.php
│   └── TeamRoleProvisioner.php
└── Support/
    ├── Parking/
    │   ├── AvailabilityFreshness.php
    │   ├── AvailabilityReportRules.php
    │   ├── ParkingIdentifier.php
    │   └── ParkingIdentifierParser.php
    └── Rbac/
        ├── Permission.php
        ├── PlatformRole.php
        └── Role.php
```

---

## Roadmap

| Milestone | Status | Details |
| :--- | :---: | :--- |
| **Geospatial Parking Discovery** | ✅ Completed | PostGIS coordinates, distance calculations, nearby search, and bounding radius |
| **Multi-Tenant Provider Management** | ✅ Completed | Filament 5 tenancy, provider onboarding, and isolated resource administration |
| **Role-Based Access Control (RBAC)** | ✅ Completed | Spatie team permissions, platform admin role, and comprehensive model policies |
| **Immutable Occupancy Reporting** | ✅ Completed | Concurrency row locking, PostgreSQL immutability triggers, and delete protection |
| **Availability Freshness & Monitoring** | ✅ Completed | Stale availability query scopes and real-time administrative monitoring dashboards |
| **Rate-Limited Availability API** | ✅ Completed | Sanctum authentication and request throttling on reporting endpoints |
| **Automated Ingestion & Sensor APIs** | 🔄 Planned | Dedicated ingestion webhooks for IoT sensors, gate barriers, and camera vision feeds |
| **Real-Time Push Alerts** | 🔄 Planned | Availability threshold notifications and favorite parking space alerts |
| **Advanced Viewport & Clustering Search** | 🔄 Planned | Map viewport bounding box queries and high-density spatial clustering |
| **Production Infrastructure** | 🔄 Planned | Horizontal scaling, Redis caching layer, and Kubernetes deployment manifests |
