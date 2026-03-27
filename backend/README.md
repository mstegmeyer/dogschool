# Dog School – Backend (Symfony)

API backend for the dog school customer management system.

## Stack

- **PHP 8.2+** with **Symfony 8**
- **Doctrine ORM** (SQLite by default; PostgreSQL/MySQL configurable via `DATABASE_URL`)
- **JWT** (Lexik JWT) for authentication
- Two separate auth systems: **customers** (email + password) and **admin users** (username + password)

## Setup

```bash
composer install
cp .env.example .env.local   # add local secrets / overrides
php bin/console doctrine:schema:create
php bin/console lexik:jwt:generate-keypair   # if keys are missing
```

**On first deploy** (or to add trainers + course types from Komm! Hundeschule):

```bash
php bin/console doctrine:fixtures:load --append
```

This loads **course types** (Kursübersicht: JUHU, MH, AGI, TK, etc.) and **trainers** (Florian, Manuela, Caro, Lea with phones). Trainers are admin users; default password is `change-me` (set `FIXTURE_ADMIN_PASSWORD` in env to override). Change passwords after first login.

Create an admin user and a customer (for testing):

```bash
php bin/console app:create-user admin <username> <password> --name="Admin Name" [--phone="..."]
php bin/console app:create-user customer <email> <password> --name="Customer Name"
```

## Authentication

- **Customer registration (no auth):** `POST /api/customer/register`  
  Body: `{"email": "...", "password": "...", "name": "..."}` (name optional). Returns the created customer (201).
- **Customer login:** `POST /api/customer/login`  
  Body: `{"email": "...", "password": "..."}`  
  Returns a JWT. Use header: `Authorization: Bearer <token>` for all `/api/customer/*` endpoints.

- **Admin login:** `POST /api/admin/login`  
  Body: `{"username": "...", "password": "..."}`  
  Returns a JWT. Use header: `Authorization: Bearer <token>` for all `/api/admin/*` endpoints.

## API Overview

### Customer API (requires `ROLE_CUSTOMER`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/customer/me` | Own profile |
| PUT/PATCH | `/api/customer/me` | Update own profile (name, email, password, address, bank account) |
| POST | `/api/customer/me/push-devices` | Register/update this customer's web push subscription |
| POST | `/api/customer/me/push-devices/unregister` | Remove this customer's stored web push subscription |
| GET | `/api/customer/dogs` | List own dogs |
| POST | `/api/customer/dogs` | Create dog |
| GET | `/api/customer/contracts` | List own contracts |
| POST | `/api/customer/contracts` | Request new contract (body: dogId, startDate, endDate optional, price, coursesPerWeek) |
| GET | `/api/customer/courses` | List all non-archived courses |
| GET | `/api/customer/courses/subscribed` | List courses the customer is subscribed to |
| POST | `/api/customer/courses/{id}/subscribe` | Subscribe to a course |
| DELETE | `/api/customer/courses/{id}/subscribe` | Unsubscribe |
| GET | `/api/customer/notifications` | List notifications for subscribed courses |

### Admin API (requires `ROLE_ADMIN`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/admin/customers` | List customers |
| POST | `/api/admin/me/push-devices` | Register/update this admin's web push subscription |
| POST | `/api/admin/me/push-devices/unregister` | Remove this admin's stored web push subscription |
| GET | `/api/admin/customers/{id}` | Get customer |
| PUT/PATCH | `/api/admin/customers/{id}` | Update customer data |
| GET | `/api/admin/contracts` | List all contracts |
| GET | `/api/admin/contracts/{id}` | Get contract |
| POST | `/api/admin/contracts/{id}/approve` | Set state to ACTIVE |
| POST | `/api/admin/contracts/{id}/decline` | Set state to DECLINED |
| POST | `/api/admin/contracts/{id}/cancel` | Set state to CANCELLED |
| GET | `/api/admin/courses` | List courses (?archived=0|1) |
| GET | `/api/admin/courses/{id}` | Get course |
| POST | `/api/admin/courses` | Create course (body: dayOfWeek 1–7, startTime, endTime, type: JUHU|MH|TK, level 0–4) |
| PUT/PATCH | `/api/admin/courses/{id}` | Update course |
| POST | `/api/admin/courses/{id}/archive` | Archive course |
| POST | `/api/admin/courses/{id}/unarchive` | Unarchive |
| GET | `/api/admin/notifications` | List notifications (?courseId= optional) |
| GET | `/api/admin/notifications/{id}` | Get notification |
| POST | `/api/admin/notifications` | Create (body: courseId, title, message) |
| PUT/PATCH | `/api/admin/notifications/{id}` | Update |
| DELETE | `/api/admin/notifications/{id}` | Delete |

## Data model (summary)

- **Customer**: name, email, password, address (street, postalCode, city, country), bank account (iban, bic, accountHolder). Has many dogs and contracts; can subscribe to many courses.
- **Dog**: name, color, gender, race; belongs to one customer.
- **Contract**: versioned (contractGroupId + version), startDate, optional endDate, dog, price, type (enum: PERPETUAL), coursesPerWeek, state (REQUESTED → ACTIVE/DECLINED → CANCELLED).
- **Course**: dayOfWeek (1–7), startTime/endTime (HH:MM), optional durationMinutes, type (JUHU, MH, TK), level (0–4), archived. Has many notifications.
- **Notification**: title, message, author (User), course, createdAt.
- **PushDevice**: token, platform (`web`), provider (`webpush`), optional deviceName. Belongs to either one customer or one admin user.
- **User** (admin): username, password, fullName.

## PWA / web push notes

- The backend stores browser push registrations and dispatches notifications through standards-based Web Push.
- Customer/admin notification registrations are stored in the `push_device` table and reused for subsequent sends.
- Notification creation runs through a dispatch hook, so sending stays decoupled from the controller flow.

### Delivery credentials

- Set `WEB_PUSH_VAPID_SUBJECT`, `WEB_PUSH_VAPID_PUBLIC_KEY`, and `WEB_PUSH_VAPID_PRIVATE_KEY` in `backend/.env.local`.
- The frontend must also receive the same public VAPID key via `NUXT_PUBLIC_WEB_PUSH_VAPID_PUBLIC_KEY`.

All entities use UUID primary keys.

## Run

```bash
symfony server:start
# or
php -S 127.0.0.1:8000 -t public
```

Then e.g. customer login: `POST http://127.0.0.1:8000/api/customer/login` with JSON body `{"email":"...","password":"..."}`.

## CI (GitHub Actions)

The repository root is expected to contain a `backend` directory (e.g. `komm` with `backend/` and `frontend/`). The workflow runs from `.github/workflows/ci.yml` and executes:

- **PHP CS Fixer** – dry run (fix with `vendor/bin/php-cs-fixer fix` locally).
- **PHPStan** – level 9 with Symfony/Doctrine/PHPUnit extensions; baseline in `phpstan-baseline.neon` (reduce over time).
- **PHPUnit** – unit and integration tests.

If your repo is only the backend, remove `working-directory: backend` from the workflow and run commands from the project root.
