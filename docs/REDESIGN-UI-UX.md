# Bengkel Pro 2.0 — UI/UX Redesign Plan

## Approved direction
- Visual language: Modern Enterprise
- Palette: navy `#142C46`, blue `#2878F0`, light canvas `#F3F6FB`, white cards, semantic green/amber/red status colors
- Primary target: desktop landscape for workshop admin, cashier, service advisor, and managers
- Secondary targets: tablet and mobile, with horizontal data tables handled intentionally
- Principle: presentation-layer changes first; preserve existing Laravel routes, permissions, business rules, database schema, APIs, and transactional behavior

## Current branch changes
- `public/css/bengkel-enterprise.css`: shared visual foundation for cards, navigation, buttons, forms, tables, badges, tabs, modals, pagination and responsive layouts
- `resources/views/layouts/app.blade.php`: loads the shared stylesheet for authenticated application screens
- `resources/views/layouts/tenant.blade.php`: loads the same visual foundation for tenant screens
- `resources/views/auth/login.blade.php`: aligns login colors and focus states with the approved brand
- `resources/views/welcome.blade.php`: landing-page visual refresh while retaining existing product configuration, CTA URLs, price labels, SEO metadata and main page structure

## Screen-by-screen design acceptance criteria

### Public marketing / landing page
- Clear product promise, source-code positioning and configurable starting price
- Primary WhatsApp CTA and demo CTA remain functional
- Product screenshots retain their aspect ratio and legibility
- Feature grid, workflow, pricing, FAQ and final CTA share the same visual system
- Responsive navigation and hero layout at mobile widths

### Authenticated application shell
- Consistent sidebar, active state, topbar, content canvas, card surfaces and typography
- Navigation remains permission-aware; no menu items or routes are removed
- Keyboard and global search behavior remains intact
- Sidebar and content remain usable at common desktop landscape resolutions and narrow screens

### Dashboard
- KPI cards visually distinguish metrics without excessive decoration
- Low-stock / critical alerts retain semantic severity
- Charts and activity tables fit their cards without overflow
- Existing widget configuration and automatic refresh remain unchanged

### Operational lists and CRUD
- Customers, vehicles, mechanics, job cards, estimates, invoices, products, purchases, suppliers, expenses, branches and other resource lists use consistent table headers, spacing, status badges and action buttons
- Search, filters, sorting, pagination, import/export and permission-gated actions remain functional
- Wide tables may scroll inside their own responsive container; the entire page must not overflow horizontally

### Service workflow
- Service intake, inspection, estimate, customer approval, mechanic work, QC, invoice, payment and gate-pass screens retain their existing sequence and data requirements
- Dense estimate/invoice builders remain usable in landscape and at 100% zoom
- Totals, discounts, taxes, parts, labor, and status information remain easy to identify
- No client-side visual change may alter price calculations or submit semantics

### Inventory / POS / finance / reports
- Stock levels and reorder warnings have clear status contrast
- POS controls and payment actions retain their existing behavior
- Finance and report tables use tabular alignment for dates and amounts
- Print/PDF templates must remain print-friendly and should not inherit decorative app-shell backgrounds

### Public customer views
- Approval, estimate, invoice, booking, tracking and customer portal pages must remain readable and branded
- Public links and approval/rejection actions must not be changed by a CSS-only redesign

## Required verification before merging
- [ ] Install dependencies in a clean environment and run `npm run build`
- [ ] Run the repository's Laravel test suite: `php artisan test`
- [ ] Run `php artisan route:list` and confirm no route changes
- [ ] Verify login, dashboard, customer CRUD, vehicle CRUD, service/estimate approval, job card, QC, invoice/payment, inventory and reports
- [ ] Verify role/permission-based visibility with at least two permission profiles
- [ ] Inspect desktop landscape at 1366x768 and 1920x1080
- [ ] Inspect tablet and mobile widths; check forms, modals, tables, sidebar and navigation
- [ ] Verify Indonesian Rupiah formatting, timezone display and date filters are unchanged
- [ ] Verify browser console and network for missing local assets or JavaScript errors
- [ ] Verify print, PDF, thermal receipt and other print-specific views
- [ ] Capture before/after screenshots for key screens and attach them to the pull request
- [ ] Only mark the pull request ready after checks pass; do not merge while visual or functional regressions remain

## Scope honesty
The shared design layer and landing/login integration are implemented in this branch. This document is a QA and acceptance plan, not a claim that every individual screen has already been manually tested. CI, browser rendering and end-to-end behavior must be verified before merging.
