# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this directory.

## Frontend — React 19 + Vite 8 + Tailwind 4

Placeholder shell with routing, auth context, and API config. Pages are stubs awaiting real implementation.

## Commands

```bash
npm install
npm run dev          # http://localhost:5173
npm run build        # production build to dist/
npm run lint         # ESLint
npm run preview      # preview production build
```

## Structure

- `src/lib/api.ts` — Axios instance (baseURL: `http://localhost:8000/api`), auto-attaches Bearer token from localStorage
- `src/lib/query.ts` — React Query client (5min stale time)
- `src/context/AuthContext.tsx` — Mock auth provider, sessionStorage persistence, `useAuth()` hook
- `src/layouts/AppLayout.tsx` — Sidebar + Topbar + Outlet
- `src/components/` — Sidebar (Vietnamese nav), Topbar, ProtectedRoute, PlaceholderPage
- `src/pages/` — LoginPage, DashboardPage (rest are PlaceholderPage instances)

## Routes (src/App.tsx)

Public: `/login`. Protected (wrapped in ProtectedRoute + AppLayout): `/`, `/attendance/*`, `/payroll/*`, `/contracts`, `/reports`, `/admin/*`.

## Styling

Tailwind 4 via `@tailwindcss/vite` plugin. Import: `@import "tailwindcss"` in `index.css`. Color scheme: slate/zinc + blue accent.

## API Integration

Token stored in `localStorage` key `"token"`. Axios interceptor reads it automatically. When building real pages, use React Query + the axios instance from `lib/api.ts`.

## When Building Real Pages

Replace `PlaceholderPage` routes with actual page components. Follow the pattern in `DashboardPage.tsx`. Use React Query for data fetching against the backend API contract defined in the root Plan.md.
