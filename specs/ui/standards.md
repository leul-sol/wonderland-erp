# UI standards — Wonderland ERP portal

Enterprise-style patterns for Inertia + Vue pages.

## Design tokens

Defined in `web-portal/resources/css/app.css`:

- **Density:** compact tables (`--wh-table-row-py`), 14px base text
- **Color:** teal primary (operations), slate neutrals, semantic status colors
- **Money:** ETB, monospace figures, right-aligned in tables

## Shared components (`resources/js/Components/`)

| Component | Use |
|-----------|-----|
| `DataTable` | Paginated lists (rooms, folio lines, PO lines) |
| `MoneyField` | ETB amount input with formatting |
| `StatusBadge` | reservation / room / folio / PO status |
| `ApprovalStepper` | Tiered PO approval, large journal GM step |
| `PageHeader` | Title + optional actions slot |

## Navigation

1. **Quick tasks** — action-first links (Check in guest, Open folios)
2. **Modules** — role-based areas (Front desk, Finance, …)

## Folio (one-screen PMS pattern)

Single `FrontDesk/Folios/Show` page:

- Guest + reservation summary
- Charge lines table (SC/VAT columns when present)
- Balance panel
- Add charge + settle forms
- Check-out when balance is zero

## Approval stepper

Map backend status to steps:

- **PO tier 1:** dept head → approved
- **PO tier 2:** dept head → finance → approved
- **PO tier 3:** dept head → finance → GM → approved

Highlight `current`, `complete`, `upcoming` per `status` + `approval_tier`.
