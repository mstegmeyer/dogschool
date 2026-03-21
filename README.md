# Komm! Hundeschule & Hundehotel

Full-stack management system for the **Komm! Hundeschule & Hundehotel** dog school. Provides a customer portal for course subscriptions, bookings, credits, and contracts, plus an admin portal for trainers to manage customers, courses, schedules, and notifications.

## Architecture

```
komm/
├── backend/          Symfony 8 REST API (PHP 8.4, Doctrine ORM, JWT auth)
├── frontend/         Nuxt 3 SPA (TypeScript, Nuxt UI, Vite)
├── docker/           FrankenPHP Dockerfile
├── docker-compose.yml
└── .github/workflows/ci.yml
```

| Component | Technology | Port |
|-----------|-----------|------|
| API | Symfony 8, Doctrine ORM, Lexik JWT | 8080 |
| Database | MariaDB 11.4 | 3306 |
| Frontend | Nuxt 3, Nuxt UI v2, TypeScript | 5173 |
| Web server | FrankenPHP (Caddy) | 8080 / 8443 |

## Quick start

### Prerequisites

- **Docker** and **Docker Compose** v2
- **Composer** (for initial `vendor/` install)

### 1. Install backend dependencies

```bash
cd backend
composer install
cd ..
```

### 2. Start the stack

```bash
docker compose up --build
```

This starts three services:

| Service | URL |
|---------|-----|
| API (FrankenPHP) | http://localhost:8080 |
| Frontend (Vite) | http://localhost:5173 |
| MariaDB | localhost:3306 |

### 3. Initialize the database

```bash
docker compose exec frankenphp php bin/console doctrine:schema:create
docker compose exec frankenphp php bin/console doctrine:fixtures:load --append
```

This creates the schema and seeds course types + default trainer accounts.

### 4. Generate JWT keys

```bash
docker compose exec frankenphp php bin/console lexik:jwt:generate-keypair
```

### 5. Create a test user (optional)

```bash
docker compose exec frankenphp php bin/console app:create-user admin trainer password123
docker compose exec frankenphp php bin/console app:create-user customer test@example.com password123 --name="Test Customer"
```

## Features

### Customer portal

- Register and log in with email + password
- Manage dogs (name, race, gender, color)
- Browse and subscribe to recurring courses
- Weekly calendar with booking / cancellation
- Credit system (weekly grants from active contracts)
- Request and view contracts
- Receive course-specific and global notifications

### Admin portal

- Dashboard with stats (customers, active courses, today's schedule, pending requests)
- Customer management with credit adjustment
- Course management (create, edit, archive, unarchive)
- Weekly calendar with cancel/reactivate for course dates
- Contract lifecycle (approve, decline, cancel)
- Notification management (global or per-course)

## Development without Docker

### Backend

```bash
cd backend
composer install
cp .env .env.local                              # adjust DATABASE_URL
php bin/console doctrine:schema:create
php bin/console lexik:jwt:generate-keypair
symfony server:start                            # or: php -S 127.0.0.1:8080 -t public
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

Set `API_PROXY_TARGET=http://localhost:8080` if the backend runs on a different host/port.

## Testing

### Backend

```bash
cd backend
vendor/bin/phpunit                    # Unit + integration tests
vendor/bin/phpstan analyse            # Static analysis (level 9)
vendor/bin/php-cs-fixer fix --dry-run # Code style check
```

### Frontend

```bash
cd frontend
npm test                              # Vitest (single run)
npm run test:watch                    # Watch mode
npm run test:coverage                 # With coverage
```

## CI (GitHub Actions)

The CI pipeline (`.github/workflows/ci.yml`) runs on pushes to `main`/`develop`, pull requests, and can be triggered manually via `workflow_dispatch`.

| Job | What it checks |
|-----|---------------|
| PHP CS Fixer | Code style (dry-run) |
| PHPStan | Static analysis at level 9 |
| PHPUnit | Backend tests with coverage |
| Vitest | Frontend tests with coverage |

Coverage summaries are posted as sticky PR comments.

## Project documentation

- **[Backend README](backend/README.md)** – API endpoints, data model, authentication details
- **[Frontend README](frontend/README.md)** – Project structure, composables, type system
