# AGENTS.md

Guidelines for AI coding agents (Cursor, Claude Code, Codex). Read fully before making changes.

## Project overview

Full-stack dog school management app. Monorepo with three workspaces:

| Directory | Stack | Purpose |
|-----------|-------|---------|
| `backend/` | Symfony 8, PHP 8.4, Doctrine ORM 3, MariaDB | REST API with JWT auth |
| `frontend/` | Nuxt 3, Vue 3, TypeScript, Pinia, Nuxt UI v2, Tailwind | SPA (SSR disabled) |
| `tests/` | Playwright | E2E functional + visual regression |

Docker Compose orchestrates all services (`docker-compose.yml`). The root `package.json` wires lint commands across workspaces.

## Git conventions

### Commits

Use **Conventional Commits**. Format: `<type>: <concise summary>`

Types: `feat`, `fix`, `refactor`, `test`, `ci`, `chore`, `docs`, `perf`

Scopes are optional but use when helpful: `feat(frontend): ...`, `fix(backend): ...`

```
feat: add course cancellation flow
fix: contract end date nullable
refactor: modularize frontend navigation
test: add unit coverage for credit service
ci: split workflows and simplify coverage comments
```

- Imperative mood, lowercase, no trailing period.
- Keep subject line under 72 characters.
- When a PR is involved, the merge commit appends `(#N)`.

### Branches

Pattern: `<type>/<short-kebab-description>`

```
feat/course-cancellation
fix/contract-nullable-enddate
refactor/navigation-menus
test/improve-coverage
ci/split-workflows
```

Default branch is `master`.

## Frontend

### Routing and modules

Routes are **not** in `pages/`. They live in `frontend/modules/` and are collected by `nuxt.config.ts` via `pages:extend`. Each `index.vue` becomes a route. Directory structure mirrors URL segments:

```
modules/
  admin/
    dashboard/index.vue          → /admin
    customers/index.vue          → /admin/customers
    customers/[id]/index.vue     → /admin/customers/:id
  customer/
    dashboard/index.vue          → /customer
    dogs/index.vue               → /customer/dogs
  auth/
    login/index.vue              → /login
    register/index.vue           → /register
```

Segments named `components`, `composables`, `types`, `utils` are skipped by the router. Use these freely as colocation folders next to page `index.vue` files.

### Component organization

- **Page-scoped components**: Place in a `components/` folder next to the page's `index.vue`. Import explicitly.
- **Shared components**: Place in `frontend/components/` (auto-imported by Nuxt, `pathPrefix: false`). Organize by domain area (`calendar/`, `skeleton/`, `notification/`, etc.).
- **Layouts**: `frontend/layouts/` — `admin.vue`, `customer.vue`, `auth.vue`, `default.vue`. Layout is resolved from `route.meta.layout` or path prefix in `app.vue`.
- **Per-module nav components**: Live at `modules/<area>/` (e.g. `CustomerNavigationMenu.vue`).

### Vue SFC conventions

- Use `<script setup lang="ts">` exclusively.
- Block order: `<template>`, `<script setup>`, `<style>` (enforced by ESLint `vue/block-order`).
- Template quotes: single quotes (`'`).
- HTML indent: 4 spaces, base indent 0.
- Use `defineProps<{...}>()` and `defineEmits<{...}>()` with TypeScript interfaces.
- Explicit function return types are enforced by ESLint.
- Use `type` imports: `import type { Foo } from '~/types'`.
- Add `data-testid` attributes to interactive elements that E2E tests target.

### State management

Use Pinia for shared frontend state. Page-local state uses `ref`/`reactive`/`computed`.

### Stores

- Place shared stores in `frontend/stores/`.
- Use setup-style stores with `defineStore(...)`.
- Put cross-route or cross-layout state in Pinia stores.
- Keep page-local form, modal, search, filter, and loading state local unless it is genuinely shared.
- Composables may wrap stores to expose app-facing APIs such as `useAuth`.

### Composables

Place in `frontend/composables/`. Key ones: `useAuth` (auth state + JWT), `useApi` (authenticated fetch + 401 handling), `useHelpers` (date formatting, etc.).

### Types

Shared types live in `frontend/types/index.ts`. Always import as `import type { ... } from '~/types'`.

### Styling

Tailwind CSS via Nuxt UI. Custom palettes `komm` and `sand` defined in `tailwind.config.ts`. Global CSS in `assets/app.css`. Prefer Tailwind utility classes; avoid custom CSS unless necessary for safe-area or mobile shell layout.

### Frontend linting

ESLint flat config at `frontend/eslint.config.mjs`. Key enforced rules:
- 4-space indent, single quotes, semicolons, 1TBS braces
- `camelCase` naming, `eqeqeq`, `curly` always
- `@typescript-eslint/explicit-function-return-type` required
- `@typescript-eslint/consistent-type-imports` (separate type imports)
- `vue/require-explicit-emits`, `vue/block-lang` (ts), `vue/html-quotes` (single)
- Max 3 attributes per single-line element

Run: `npm run lint` (from `frontend/`) or `npm run lint` (from root, runs both frontend + tests).

### Frontend testing (Vitest)

- Config: `frontend/vitest.config.ts`. DOM: `happy-dom`.
- Test files: `*.test.ts`, colocated next to source or in `frontend/tests/`.
- Use `@vue/test-utils` for component mounting.
- Initialize a fresh Pinia instance in unit tests that exercise stores or Pinia-backed composables.
- Coverage scope: `app.vue`, `layouts/`, `components/`, `modules/`, `composables/`, `stores/`, `middleware/`.

Run: `npm run test` (from `frontend/`).

## Backend

### PHP code style

- `declare(strict_types=1)` in every PHP file.
- PHP CS Fixer with `@Symfony` ruleset, yoda style disabled. Config: `backend/.php-cs-fixer.dist.php`.
- Final classes for services and controllers.
- Constructor property promotion with `private readonly`.
- Doctrine PHP 8 attributes (`#[ORM\Entity]`, `#[ORM\Column]`, etc.).
- Symfony PHP 8 attributes for routes (`#[Route]`), security (`#[IsGranted]`), etc.

Run: `vendor/bin/php-cs-fixer fix --dry-run --diff` (from `backend/`).

### Static analysis (PHPStan)

Level **9** (maximum). Config: `backend/phpstan.neon`. Includes Symfony, Doctrine, and PHPUnit extensions.

Every new PHP code must pass PHPStan level 9. Use `@phpstan-type`, `@phpstan-param`, `@phpstan-return` annotations where generic types are needed.

Run: `vendor/bin/phpstan analyse` (from `backend/`, after `php bin/console cache:warmup`).

### Controllers

- Namespaced under `App\Controller\Api\Admin\` or `App\Controller\Api\Customer\`.
- Extend `AbstractController`. Use constructor DI with repository + service classes.
- Class-level `#[Route('/api/<area>/...')]` prefix, method-level `#[Route]` for actions.
- Use `$this->json()` for responses. Normalize via `ApiNormalizer` service.
- Current `Customer` or `User` is resolved via `CurrentCustomerOrUserValueResolver` — just type-hint the parameter.

### Entities

- Under `App\Entity\`. Embeddable value objects in `App\Entity\Embeddable\`.
- UUID string IDs with `#[ORM\GeneratedValue(strategy: 'NONE')]`.
- Use Symfony Validator constraints where appropriate.

### Services

- Under `App\Service\`. Final classes, constructor DI, EntityManager for persistence.

### Repositories

- Under `App\Repository\`. Extend `ServiceEntityRepository`.

### Database

- No Doctrine migrations. Schema managed via `doctrine:schema:create` / `doctrine:schema:update`.
- Fixtures in `App\DataFixtures\` (demo data) and `App\E2e\E2eSeedService` (E2E seed).

### Backend testing (PHPUnit)

- Config: `backend/phpunit.xml.dist`. Bootstrap drops and recreates schema.
- Test directories: `tests/Unit/`, `tests/Integration/`.
- Naming: `*Test.php`, method names `test<Behavior>` (no `@test` annotations).
- Integration tests use `WebTestCase::createClient()` and `ApiTestHelper` for authenticated requests.
- Unit tests extend PHPUnit `TestCase` or the custom `App\Tests\KernelTestCase`.

Run: `php bin/phpunit` (from `backend/`).

## E2E testing (Playwright)

Config: `tests/playwright.config.ts`. Two projects: `functional` and `visual`.

### Structure

```
tests/
  e2e/
    functional/     ← Behavioral tests (*.spec.ts)
    visual/         ← Screenshot regression tests (*.spec.ts)
    fixtures/       ← Custom Playwright fixtures (test.ts, manifest.ts)
    page-objects/   ← Page Object classes (AuthPage.ts, etc.)
    helpers/        ← Shared helpers (auth.ts, browser.ts, ui.ts)
  snapshots/        ← Committed baseline screenshots (by project)
```

### Writing functional tests

- Import `{ test, expect }` from `../fixtures/test` (not from `@playwright/test` directly).
- Use custom fixtures: `manifest`, `loginAsCustomer(persona)`, `loginAsAdmin()`, `expectToast(title)`.
- The `manifest` fixture provides deterministic IDs, credentials, and entity references created by `E2eSeedService`. Never hardcode IDs or emails.
- Use Page Object classes for repeated UI interactions.
- Name spec files after the feature area: `auth.spec.ts`, `customer-dashboard.spec.ts`, `admin-course-management.spec.ts`.
- Prefer accessible locators: `getByRole`, `getByText`, `getByLabel`, `getByTestId`.

### Writing visual tests

- Same imports from `../fixtures/test`.
- Call `waitForVisualReady()` before screenshots (waits for network idle, fonts, blurs active element).
- Screenshot assertion: `await expect(page).toHaveScreenshot('area/name.png', { fullPage: true })`.
- Snapshots are stored in `tests/snapshots/<projectName>/` and committed to git.
- The browser clock is frozen and animations are disabled for determinism (see `helpers/browser.ts`).
- Visual tests run on `ubuntu-latest` in CI with `--font-render-hinting=medium`.

### Updating visual baselines

When you intentionally change the UI:

1. Run `npm run test:visual:update` (from `tests/`) locally — this will fail on macOS vs CI Linux.
2. Baselines must match the CI environment (Ubuntu + Chromium). Either:
   - Push the code change and let CI generate new baselines via a follow-up commit, or
   - Run updates inside a matching Linux container.
3. Commit updated snapshots from `tests/snapshots/` alongside the UI change.

### E2E seed data

The backend's `E2eSeedService` (at `backend/src/E2e/E2eSeedService.php`) creates all test data and writes a JSON manifest to `tests/.cache/e2e-manifest.json`. Playwright reads this via `readManifest()`.

When adding new features that need E2E coverage:
1. Add a new persona or entity in `E2eSeedService.seed()`.
2. Extend the `E2eManifest` TypeScript type in `tests/e2e/fixtures/manifest.ts`.
3. Reference manifest values in tests instead of hardcoding.

### Adding new E2E test personas

Add the persona key to the `CustomerPersona` union type in `manifest.ts`, add the definition in `E2eSeedService::PERSONAS`, and create necessary relationships in the seed method.

## CI pipeline

Three GitHub Actions workflows (`.github/workflows/`):

### `frontend.yml`
- **lint**: ESLint on `frontend/` and `tests/`
- **vitest**: Unit tests with coverage + PR coverage comment

### `php.yml`
- **php-cs-fixer**: Code style check
- **phpstan**: Static analysis (level 9)
- **phpunit**: Tests with coverage + PR coverage comment

### `e2e.yml`
- **playwright-functional**: `--project=functional`
- **playwright-visual**: `--project=visual`

All workflows trigger on push/PR to `main`, `master`, `develop`.

## Before submitting code

Checklist for every change:

1. **Lint passes**: `npm run lint` from root (frontend + tests ESLint).
2. **PHP CS Fixer passes**: `vendor/bin/php-cs-fixer fix --dry-run` in `backend/`.
3. **PHPStan passes**: `vendor/bin/phpstan analyse` in `backend/` (level 9).
4. **Unit tests pass**: `npm run test` in `frontend/`, `php bin/phpunit` in `backend/`.
5. **New behavior has tests**: Unit tests for business logic, integration tests for API endpoints, E2E tests for user-facing flows.
6. **Visual changes are noted**: If UI changed, visual snapshots will need updating. Flag this in the PR description.

## What NOT to do

- Do not use `pages/` directory for routes — use `modules/`.
- Do not move simple page-local state into Pinia without a real shared-state need.
- Do not add Prettier — ESLint handles all formatting via `@stylistic`.
- Do not use Yoda comparisons in PHP (`$value === null`, not `null === $value`).
- Do not create Doctrine migrations — schema is managed directly.
- Do not hardcode entity IDs or credentials in E2E tests — use the manifest.
- Do not import `test`/`expect` from `@playwright/test` in E2E tests — use `../fixtures/test`.
- Do not skip `declare(strict_types=1)` in PHP files.
- Do not use `any` as a type in TypeScript without justification.
