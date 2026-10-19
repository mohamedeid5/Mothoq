# Mothoq | موثوق

**English** | [العربية](README.ar.md)

An automotive service discovery and booking platform for Egypt. Mothoq helps drivers find service centers, explore their services and reviews, and book appointments through an Arabic web interface and a versioned REST API.

Customers manage their bookings, center owners manage their locations and availability, and administrators oversee center verification and review moderation.

## Contents

- [Features](#features)
- [Technology stack](#technology-stack)
- [Run with Docker](#run-with-docker)
- [Run without Docker](#run-without-docker)
- [Environment configuration](#environment-configuration)
- [API overview](#api-overview)
- [Booking rules](#booking-rules)
- [Project structure](#project-structure)
- [Testing](#testing)
- [Operations and deployment](#operations-and-deployment)
- [Troubleshooting](#troubleshooting)

## Features

- **Service discovery:** filter centers by governorate, city, service, car brand, and verification status.
- **Center profiles:** browse services, photos, opening hours, and customer reviews.
- **Appointment management:** create, cancel, and reschedule bookings with service duration and capacity checks.
- **Scheduling:** configure weekly opening hours and date-specific exceptions, including closures.
- **Owner tools:** manage authorized centers, photos, availability, and bookings.
- **Administration:** manage center status and verification, and moderate reviews.
- **Authentication:** web registration, login, and password recovery, plus Sanctum API tokens.
- **Background processing:** booking emails and Redis queues managed by Horizon.

## Technology stack

| Component | Technology |
| --- | --- |
| Backend | PHP 8.4, Laravel 13 |
| Authentication | Laravel Sanctum 4 |
| Frontend | Blade, Tailwind CSS 4, Vite 8 |
| Database | MySQL 8.4 in Docker; SQLite for default tests |
| Cache, sessions, and queues | Redis, Laravel Horizon 5 |
| Testing | PHPUnit 12 |
| Tooling | Composer 2, Node.js 24, Docker Compose |
| Storage and mail | Local or S3 storage; local mail logging with SES support |

Exact dependency versions are recorded in [composer.lock](composer.lock) and [package-lock.json](package-lock.json).

## Run with Docker

Install Git and Docker with Compose, clone the repository, and run the following commands from the project root.

### 1. Configure the environment

For a fresh checkout:

```bash
cp .env.example .env
```

The defaults use `mysql` and `redis` as Docker service hostnames. Adjust the published ports in `.env` if they are already in use.

### 2. Build and start the application

```bash
docker compose -f compose.yaml -f compose.dev.yaml up -d --build mysql redis app nginx node
```

The development image installs Composer dependencies. Its entrypoint generates a missing application key and creates the storage link. The `node` service installs frontend dependencies and starts Vite.

### 3. Migrate the database and start queues

```bash
docker compose -f compose.yaml -f compose.dev.yaml exec app php artisan migrate --no-interaction
docker compose -f compose.yaml -f compose.dev.yaml up -d horizon
```

The default local web port is `8080`; Vite uses `5173`. Set `APP_URL` to match the address you use to access the application.

### 4. Load optional demo data

Run this against a development database only:

```bash
docker compose -f compose.yaml -f compose.dev.yaml exec app php artisan db:seed --no-interaction
```

The seeders populate locations, services, vehicle catalogs, centers, reviews, and bookings. They also create these development accounts:

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@mothoq.test` | `password` |
| Center owner | `owner@mothoq.test` | `password` |

Do not seed these demo accounts into production. Create a customer account through the registration page.

To stop the services while retaining their data:

```bash
docker compose -f compose.yaml -f compose.dev.yaml down
```

## Run without Docker

Use PHP 8.4, Composer 2, Node.js 24, MySQL, and Redis. See the [development Dockerfile](docker/php/Dockerfile) for the PHP extensions used by the project, including `pdo_mysql`, `pdo_sqlite`, `redis`, and `pcntl`.

For a fresh checkout:

```bash
composer install
cp .env.example .env
php artisan key:generate --no-interaction
npm ci
```

Before migrating, create the database and its user, then update `.env`:

- Set `DB_HOST=127.0.0.1` and `REDIS_HOST=127.0.0.1` for local services.
- Configure `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for your installation.
- If PHP runs on your host while MySQL and Redis run in the development containers, their default published ports are `33061` and `63790`, respectively.

Then run:

```bash
php artisan migrate --no-interaction
php artisan storage:link --no-interaction
npm run build
php artisan serve --port=8080 --no-interaction
```

During development, run each of the following in a separate terminal:

```bash
npm run dev
php artisan horizon --no-interaction
php artisan schedule:work --no-interaction
```

## Environment configuration

See [.env.example](.env.example) for the complete template.

| Variable | Purpose |
| --- | --- |
| `APP_URL` | Application address used to generate links |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | Arabic by default, with English as the fallback |
| `DB_*` | Database connection settings |
| `REDIS_*` | Redis connection settings; the default client is `phpredis` |
| `SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` | Default to `redis` |
| `SANCTUM_EXPIRATION` | API token lifetime in minutes; defaults to `43200` |
| `CENTER_IMAGES_DISK` | Center image storage: `public` locally or `s3` |
| `AWS_*` | AWS storage and service configuration |
| `MAIL_MAILER` | Defaults to `log` locally, writing emails to logs instead of sending them |
| `MYSQL_ATTR_SSL_CA` | CA certificate path for encrypted MySQL connections in production |
| `APP_PORT` / `VITE_PORT` | Published Docker ports |

The `.env` file is excluded from Git. Keep secrets outside the repository.

## API overview

All paths below are relative to `/api/v1`. Send `Accept: application/json`. Protected requests also require `Authorization: Bearer <token>`.

### Authentication

| Method | Path | Purpose |
| --- | --- | --- |
| POST | `/auth/register` | Register an account |
| POST | `/auth/login` | Issue an API token |
| GET | `/auth/me` | Retrieve the current user |
| DELETE | `/auth/logout` | Revoke the current token |

Example login body:

```json
{
  "email": "customer@example.com",
  "password": "your-password",
  "device_name": "local-client"
}
```

Use an existing account's credentials. The response contains the user in `data.user` and the token in `data.token`.

### Public catalog

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/governorates` | List governorates |
| GET | `/governorates/{governorate}/cities` | List cities in a governorate |
| GET | `/services` | List services |
| GET | `/car-brands` | List car brands |
| GET | `/service-centers` | List and filter centers |
| GET | `/service-centers/{slug}` | Retrieve a center profile |
| GET | `/service-centers/{slug}/schedule?date=YYYY-MM-DD` | Retrieve a date's schedule |

Center filters accept `governorate`, `city`, `service`, and `car_brand` as slugs, plus `verified`, `page`, and `per_page`. The maximum page size is 50. A city filter requires its matching governorate.

### Customer bookings

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/bookings` | List the current customer's bookings |
| POST | `/service-centers/{slug}/bookings` | Create a booking |
| DELETE | `/bookings/{id}` | Cancel a booking |
| PATCH | `/bookings/{id}/reschedule` | Reschedule a booking |

Creating a booking requires the `customer` role and an `Idempotency-Key` header containing a UUID v4. Generate a new key for each new logical request; reuse the same key and payload when retrying that request.

Example booking body; replace the service and appointment with available values:

```json
{
  "service_id": 1,
  "customer_phone": "01012345678",
  "scheduled_at": "2027-01-15T10:00",
  "notes": "Routine vehicle inspection"
}
```

The appointment must be in the future and use Egypt local time without `Z` or an offset such as `+02:00`. Service duration comes from the center's configuration rather than client input.

### Reviews and management

- Create a review with `POST /service-centers/{slug}/reviews`.
- Update or delete a review with `PATCH /reviews/{id}` or `DELETE /reviews/{id}`, subject to authorization.
- Owner routes use the `/owner` prefix, require the `center_owner` role, and are scoped to authorized resources.
- Administrator routes use the `/admin` prefix and require the `admin` role.
- Center management covers photos, opening hours, schedule exceptions, service durations, and booking capacity. Administration also covers reviews, center status, and verification.

This is an endpoint overview, not a complete request schema reference. See the [API routes](routes/api.php) and [request validation classes](app/Http/Requests), or inspect the registered routes:

```bash
php artisan route:list --path=api --except-vendor --no-interaction
```

## Booking rules

- Appointment times are stored and compared in Egypt local time (`Africa/Cairo`).
- Date exceptions override weekly opening hours. An unconfigured schedule is unavailable.
- `pending` and `accepted` bookings consume capacity.
- Center capacity and service duration must be configured before accepting bookings. Missing values do not imply unlimited capacity or a default duration.
- Each booking stores its end time and a snapshot of its service duration. Later duration changes do not recalculate existing booking end times.
- Schedule changes must preserve active bookings. Incomplete historical data may require review before a change can proceed.

The [scheduling guide](SCHEDULING.md) provides additional detail in Arabic. To audit scheduling data without modifying it:

```bash
php artisan scheduling:audit --no-interaction
```

## Project structure

| Path | Responsibility |
| --- | --- |
| `app/Actions` | Business operations such as creating bookings and updating schedules |
| `app/Data` | Data transfer objects |
| `app/Queries` | Listing and filtering queries |
| `app/Models` / `app/Enums` | Data models, roles, and statuses |
| `app/Http/Controllers` | Web and API entry points |
| `app/Http/Requests` / `app/Http/Resources` | Input validation and API response formatting |
| `app/Policies` | Resource authorization |
| `resources` | Blade templates, CSS, and JavaScript |
| `database` | Migrations, factories, and seeders |
| `routes` | Web routes, API routes, and scheduled tasks |
| `tests` | Unit and feature tests |
| `docker` / `infra` | Container, deployment, and infrastructure configuration |

## Testing

The default suite uses in-memory SQLite and test-specific mail, queue, and cache settings defined in [phpunit.xml](phpunit.xml).

```bash
php artisan test --compact --no-interaction
```

Run a focused test file:

```bash
php artisan test --compact tests/Feature/Api/V1/BookingControllerTest.php --no-interaction
```

Inside Docker, prefix PHP commands with `docker compose -f compose.yaml -f compose.dev.yaml exec app`.

MySQL concurrency tests require a separate test database; CI provisions one for this purpose. Do not point tests at a development or production database containing data you need to retain.

After changing PHP files, format the changes before submitting:

```bash
vendor/bin/pint --dirty --format agent
```

## Operations and deployment

The [GitHub Actions workflow](.github/workflows/ci-cd.yml) checks deployment configuration, runs PHP and MySQL tests, builds frontend assets, and builds production container images. Pushes to `main`, or manual runs on that branch, also trigger AWS deployment after the checks pass. Deployment requires the project's AWS resources and OIDC permissions to be configured.

- The [production Dockerfile](docker/Dockerfile.prod) builds application and Nginx images.
- The [EC2 configuration](docker/compose.ec2.yaml) and [Auto Scaling configuration](docker/compose.autoscaling.yaml) define production services.
- Run Horizon for queue processing and the Laravel scheduler for recurring tasks.
- [Scheduled tasks](routes/console.php) capture Horizon metrics every five minutes and prune expired idempotency keys hourly.
- `php artisan app:check-scaling-readiness --no-interaction` checks shared production state for Auto Scaling: RDS, encrypted Redis connections, and S3 center images. It is not a local development prerequisite.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| MySQL or Redis connection failure | Hostnames and ports differ between Docker and host execution |
| Missing assets or Vite manifest | Run `npm run build` or keep `npm run dev` running |
| Center images do not load | Check `CENTER_IMAGES_DISK` and the `public/storage` link when using `public` |
| Local emails do not arrive | `MAIL_MAILER=log` writes emails to application logs |
| Queued jobs do not run | Check Horizon and Redis connectivity |
| A booking is rejected during opening hours | Check service duration, capacity, overlapping bookings, and schedule exceptions |

Inspect local service logs:

```bash
docker compose -f compose.yaml -f compose.dev.yaml logs --tail=100 app horizon nginx
```
