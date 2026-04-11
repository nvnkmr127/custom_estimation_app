# Fix All Audit Findings — Design

Date: 2026-04-11  
Project: Custom Estimation App (Laravel + Livewire + Blade)  

## Goal

Resolve the full system audit findings across backend, frontend, and end-to-end user flows so the application behaves like a production-ready system:

- No broken pages (runtime errors, missing components, missing controller actions).
- No navigation targets that lead to 403s or raw JSON responses.
- Authorization is consistent across routes, navigation, controllers, and Livewire actions.
- CRUD affordances exist and match the underlying routes and policies.
- Dead links (`href="#"`) are eliminated or replaced with real destinations.
- Frontend ↔ backend contracts are consistent (HTML pages return HTML/redirects; XHR endpoints return JSON and are not used as nav destinations).

## Non-Goals

- Major redesign or UI overhaul beyond fixing broken/incomplete journeys and standardizing feedback.
- Large-scale refactors not required to fix a finding (e.g., replatforming estimation builder to full Livewire).
- Introducing new role types beyond what exists today; focus is on consistency.

## Key Decisions

1. **Stabilize → Normalize → Cleanup** (execution order)
   - Stabilize: fix runtime breakers and navigation dead-ends first.
   - Normalize: align authorization and response contracts across modules.
   - Cleanup: remove or consolidate unreachable/duplicate artifacts only when proven unused.

2. **Keep `super_admin`-only modules super-admin-only**
   - Fix navigation gating so `estimator_admin` does not see `super_admin`-only links.
   - Do not broaden route middleware unless explicitly requested later.

3. **Authorization source of truth**
   - Prefer explicit route middleware for coarse gating (role) and policies/gates for fine-grained abilities.
   - Navigation rendering must enforce the same gating logic (role/ability checks) to prevent 403 dead ends.

4. **Contract separation**
   - Navigation destinations must always return HTML views.
   - JSON endpoints remain available for XHR usage and are never used as menu targets.

## Scope (By Theme)

### 1) Hard Breakers

- Notifications page renders (missing Livewire component).
- Automation Templates nav no longer points at a JSON endpoint.
- Webhooks resource routing does not reference missing controller actions.
- Any other route→controller/view mismatches found during implementation are fixed as part of this theme.

### 2) Authorization + Navigation Alignment

- Sidebar rendering enforces per-item role/ability, including any existing `role` metadata.
- Settings mutations enforce `manage_settings` consistently (edit/update/test-email/gallery deletion).
- Reports access enforces `view_reports` consistently (route or Livewire mount).
- Webhooks access is consistent between route middleware and policies (no “allowed by policy, blocked by route” mismatch).
- Role taxonomy is made internally consistent to avoid drift-based access bugs.

### 3) CRUD + UX Completeness

- Add missing UI actions where routes exist and the feature is clearly intended:
  - Clients: delete.
  - Tasks: delete.
  - Reminders: add create UI or explicitly lock down route + clarify as system-only.
- Approval / change-request checklist show route/view parity:
  - Either implement show views and link to them, or remove show routes if not needed.

### 4) UI Wiring + Dead Links

- Replace all `href="#"` usages in user-facing screens with correct links or remove the control.
- Fix “View Chain” affordance to a real destination appropriate to role access.
- Global search modal: encode queries, correct shortcut copy, add error states.

### 5) Consistency Cleanup

- Remove or consolidate orphan/unreachable views and legacy controllers only once confirmed unused.
- Normalize web routes vs JSON endpoints within modules (e.g., product retire/activate; webhook controller vs Livewire).

## Acceptance Criteria

- All routes that are linked from UI navigation render HTML pages (no JSON in browser, no missing methods).
- `estimator_admin` no longer sees `super_admin`-only menu items and cannot access the routes.
- `/notifications` renders without runtime errors; users can mark read and navigate to linked resources when provided.
- Settings update paths are permission-protected identically to settings view paths.
- Clients and Tasks have delete affordances with confirmation and appropriate authorization/error handling.
- All known `href="#"` dead links in core surfaces are replaced with real targets or removed.

## Testing / Verification

- Add/extend feature tests for:
  - Notifications index renders.
  - Menu gating (super_admin-only items hidden for estimator_admin) and route access remains 403.
  - Settings update requires `manage_settings`.
  - Automation templates nav target returns HTML (and JSON endpoint is not used as nav).
  - Webhooks resource routes do not reference unimplemented controller actions.
- Run the application test suite and ensure no regressions.

## Risks / Mitigations

- **Role drift**: multiple role strings in use can cause hidden access bugs. Mitigation: consolidate role checks and add tests.
- **Orphan cleanup regressions**: removing “unused” views can break hidden links. Mitigation: only remove when proven unreachable; prefer deprecating first.
- **Mixed UI paradigms** (Livewire vs controller): inconsistency can cause feedback or redirect mismatches. Mitigation: normalize response types per surface and add coverage.

