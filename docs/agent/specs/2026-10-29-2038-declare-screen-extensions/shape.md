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
