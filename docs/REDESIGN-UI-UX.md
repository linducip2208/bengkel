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

## Verification log (this branch, executed)
- `php artisan view:cache` — Blade templates cached successfully (all 375 views compile).
- `php artisan route:list --name=roles.index` — route intact (`Tenant\RoleController@roleIndex`).
- `php artisan test --filter=RolePermissionEnforcementTest` — 6/6 passed (edit-di-roles berdampak, POS discount gate, estimates override gate).
- `npm run build` (vite v8.3.3) — built in 222ms, `manifest.json` + app css/js OK. Note: `bengkel-enterprise.css` is static (loaded via `asset()`), not part of Vite bundle — by design, no CDN added.
- Table audit (307 `<table>` across views): screen lists wrapped in `.table-responsive`; unwrapped tables are print/PDF/email/receipt templates handled by print CSS — no page-level horizontal overflow by design.
- Visual fixes applied and verified by code inspection (no browser automation available in this environment):
  1. `layouts/app.blade.php` design tokens aligned to enterprise (`--sidebar-width:264px`, `--sidebar-bg:#142c46`, `--topbar-height:64px`, `--bg:#f3f6fb`, `--text:#172d45`, `--text-muted:#718198`, `--border:#e2eaf3`); removed `!important` on `.text-muted` so dark mode still works.
  2. `bengkel-enterprise.css`: `.text-muted/.form-text` override scoped to `body:not(.dark)`; added viewport-safe `.dropdown-menu`/`.modal-dialog` caps, `:focus-visible` ring, `.num` tabular numbers, `.empty-state` helper, `.table-responsive` touch scrolling.
  3. `auth/login.blade.php`: palette unified to `#2878f0/#1766dc`/navy `#142c46`; removed dead `href="#"` links (no password-reset route exists — documented as missing feature, not faked).
  4. `layouts/app.blade.php` user dropdown: removed fake Profile/Settings links; Settings now points to real `settings.index` gated by `@can('settings.view')`.
  5. `welcome.blade.php`: focus ring unified to enterprise blue; CTAs verified real (WhatsApp `$waLink`, anchors `#fitur`, `/docs`, `/harga-aplikasi-bengkel`, `/sitemap.xml`); screenshots verified present in `public/marketing/screens/` (incl. `dashboard.png`).
- Remaining / known limitations:
  - `layouts/tenant.blade.php` is not extended by any view (0 usages; all 279 extend `layouts.app`) — kept as legacy, loads same CSS, no functional impact.
  - No `profile` route and no password-reset flow exist in `routes/web.php` — login honestly omits them instead of fake links.
  - Manual browser + print/PDF + 1366/1920 landscape checks still required before merge (no browser automation in this environment); screenshots not captured here.

## Verification log — ronde 2 (CRUD/modules, executed)
- `php artisan test` (full suite) — 435 tests, 434 passed, 1 skipped, 0 failed.
- `php artisan view:cache` — OK setelah semua edit blade ronde 2.
- Perbaikan yang diterapkan (presentation/UX only, tanpa ubah proses bisnis, permission, route, atau logika nominal):
  1. `dashboard.blade.php` — perbaiki 2 tag `<a>` tak tertutup (Outstanding Invoices, Low Stock); Quick Actions kini digate `@can(service|customer|invoice.create)`; bungkus `flex-wrap`.
  2. `users/index.blade.php` — kolom menyesatkan `Last Login` (isi `updated_at`) diganti `Diperbarui`.
  3. `reports/service.blade.php` — hapus kartu duplikat `Total Estimasi (Charge)` yang memakai variabel sama dengan `Total Revenue`; kedua tabel breakdown kini dalam `.table-responsive`.
  4. `errors/404,500,generic.blade.php` — token brand disatukan `#2878f0`.
  5. `customers|vehicles/index.blade.php` — pagination `withQueryString()` (filter tidak hilang pindah halaman); empty-state ikon + CTA tambah (gated).
  6. `products|suppliers/index.blade.php` — empty-state ikon + CTA tambah (gated); `suppliers/index` Tambah/Detail/Edit kini digate `supplier.create/view/edit` + `aria-label` (sebelumnya hanya delete yang digate).
- Temuan audit yang disengaja dibiarkan (butuh keputusan produk/test khusus, bukan redesign): auto-refresh dashboard via `fetch+innerHTML` tanpa error handling, kalkulasi nominal di view invoices, N+1 `WorkshopProgressService` per baris, OTP tanpa resend timer, `register/forgot-password` memang tidak ada (login jujur tanpa link palsu).
