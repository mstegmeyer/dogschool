# Komm! Hundeschule – Frontend

Customer and admin portal for the **Komm! Hundeschule & Hundehotel** dog school management system, built with [Nuxt 3](https://nuxt.com), [Nuxt UI](https://ui.nuxt.com) and TypeScript.

## Stack

| Layer | Technology |
|-------|-----------|
| Framework | Nuxt 3 (SPA mode, `ssr: false`) |
| UI | Nuxt UI v2 (Tailwind CSS, Heroicons) |
| Language | TypeScript (strict mode) |
| Testing | Vitest + Vue Test Utils + happy-dom |
| Dev server | Vite on port 5173 |

## Project structure

```
frontend/
├── app.vue                  # Root component
├── nuxt.config.ts           # Nuxt configuration (proxy, meta)
├── tsconfig.json            # Extends Nuxt-generated config
├── composables/
│   ├── useApi.ts            # Typed $fetch wrapper with JWT + 401 handling
│   ├── useAuth.ts           # Auth state (token, role, user), login/logout
│   └── useHelpers.ts        # Date formatting, labels, domain helpers
├── middleware/
│   └── auth.global.ts       # Route guards (public vs admin vs customer)
├── types/
│   └── index.ts             # Shared domain interfaces + API types
├── layouts/
│   ├── default.vue          # Minimal shell
│   ├── auth.vue             # Centered card layout for login/register
│   ├── admin.vue            # Sidebar navigation for admin area
│   └── customer.vue         # Sidebar navigation for customer area
├── pages/
│   ├── login.vue            # Tabbed login (customer / admin)
│   ├── register.vue         # Customer registration
│   ├── admin/               # Admin pages (dashboard, customers, courses, calendar, contracts, notifications)
│   └── customer/            # Customer pages (dashboard, profile, dogs, courses, calendar, credits, contracts, notifications)
└── tests/                   # Vitest unit tests
```

## Prerequisites

- **Node.js 22+** (LTS recommended)
- **npm** (included with Node)

## Setup

```bash
npm install
```

## Development

```bash
npm run dev
```

The dev server starts at `http://localhost:5173`. API requests to `/api/*` are proxied to the backend (default `http://localhost:8080`). Override the target with the `API_PROXY_TARGET` environment variable.

### With Docker Compose (recommended)

From the **repository root**:

```bash
docker compose up --build
```

This starts the backend (FrankenPHP on port 8080), MariaDB, and the frontend (port 5173) together. The frontend container runs `npm install && npm run dev` automatically.

## Testing

```bash
npm test              # Single run
npm run test:watch    # Watch mode
npm run test:coverage # With coverage report
```

Tests use **Vitest** with `happy-dom` as the DOM environment. Coverage reports are generated in `coverage/`.

## Build for production

```bash
npm run build
npm run preview   # Preview the production build locally
```

## Type system

All domain entities, API response shapes, and form payloads are defined in `types/index.ts`. The project uses TypeScript strict mode with `noUncheckedIndexedAccess` enabled. Every Vue component uses `<script setup lang="ts">`.

Key types:

- `ApiListResponse<T>` – generic wrapper for paginated list endpoints (`{ items: T[] }`)
- `ContractState`, `CreditTransactionType`, `DayOfWeek` – union/literal types for domain enums
- `ProfileUpdatePayload`, `BookingResponse`, `CustomerCreditsResponse` – API-specific shapes

## Composables

| Composable | Purpose |
|------------|---------|
| `useAuth()` | Manages JWT token, role, user profile; provides login/logout/register |
| `useApi()` | Typed HTTP client (`get`, `post`, `put`, `del`) with auto-auth and 401 redirect |
| `useHelpers()` | German date/time formatting, contract state labels/colors, credit type labels |
