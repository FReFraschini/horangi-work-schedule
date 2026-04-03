# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A Laravel 12 + Vue 3 work schedule management app with two user roles: **gestore** (manager) and **operatore** (operator). All application code lives under `code/`.

## Commands

All commands run from `code/`:

```bash
# First-time setup
composer setup        # installs deps, creates .env, generates app key, migrates DB, builds frontend

# Development
composer dev          # starts Laravel server + queue + logs + Vite watcher concurrently
npm run dev           # Vite dev server only

# Production build
npm run build

# Tests
composer test         # PHPUnit test suite
php artisan test      # alternative
```

## Architecture

### Backend (Laravel 12)

- **Auth:** Session-based for web routes; Laravel Sanctum (`auth:sanctum`) for all API routes
- **Authorization:** Single Gate `is-gestore` — gestore-only API routes are grouped under this gate check
- **API structure:**
  - `routes/api.php` splits into two middleware groups: admin (gestore) and operator
  - Controllers in `app/Http/Controllers/Api/` (gestore) and `app/Http/Controllers/Api/Operator/` (operator)

**Data models:**
```
User (role: 'gestore'|'operatore', weekly_hours)
├── Shift (user_id, start_time, end_time)
└── UnavailabilityRequest (user_id, date, preference, status: 'pending'|'approved'|'rejected')
```

### Frontend (Vue 3 + Vuetify 4)

- Entry point: `resources/js/app.js` — creates Vue app, registers components globally, mounts to `#app`
- Two page-level components in `resources/js/pages/`:
  - `ScheduleDashboard.vue` — gestore view: weekly planning, shift CRUD, request approval
  - `OperatorDashboard.vue` — operator view: personal shifts, unavailability request submission
- Blade template `resources/views/home.blade.php` renders the correct dashboard based on the `is-gestore` gate result (passed as a prop/variable from `HomeController`)
- Main layout: `resources/views/layouts/app.blade.php` — Vuetify shell with nav bar

**Vuetify theme:** Custom soft-blue theme defined in `resources/js/app.js`.

**Vite config note:** `vue` is aliased to `vue/dist/vue.esm-bundler.js` in `vite.config.js` to support runtime template compilation.

### API communication

Frontend calls the `/api/` endpoints via Axios with the CSRF token set as the default header (`X-XSRF-TOKEN` via cookie). All API responses are JSON.
