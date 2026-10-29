# Declare screen extensions (Polycart) — Shaping Notes

## Scope

Move Polycart's screens onto Atrium's screen extensions registry (Atrium `2026-10-29-2037-screen-extensions-registry`).

- **Inline audit trails.** Remove `<x-atrium::audit-trail>` from `ui/carts/index` (package-wide) and `ui/carts/show` (subject: the cart). Declare the `polycart.carts` and `polycart.cart` screens with their audited subjects, so Keen's History section attaches.
- **Out of scope (section B):** `historyNavItem`, and the audit listeners gated on `Package::audits()`.

## Decisions

- D-001: declarations only. Polycart registers nothing into other packages.

## Context

- **Depends on:** Atrium `screen-extensions-registry`. Pairs with Keen `history-section-by-capability`.
- **References:** see `references.md`.

## Standards

- architecture/dependencies: no inline checks for optional packages in views.
- atrium/components: `x-atrium::*` only, so `StylesTest` stays green.
- testing/test-layers: feature tests assert each screen declares its subject, and the existing suites stay green.

## Addendum (2026-10-29): every screen declares slots and tab bar

User decision (Atrium D-013): every Atrium screen in this package declares itself with `Screen::make("{package}.{screen}")`, renders the Atrium tab bar (only visible once at least 2 tabs are visible), and declares the standard slots: `header.actions`, `before` and `after` on all screens; `aside` and `history` (when audited) on detail screens; the `row.actions` row slot on list screens. Screens without built-in tabs get a default `overview` tab. With nothing registered, every existing feature and browser test passes unchanged. A feature test per screen registers a tab and a section in each standard slot and asserts that they render.
Polycart screens: carts index, cart show, types.
