# Amarvero

Amarvero is a Laravel 13 footwear e-commerce application built on the official Livewire starter kit. It now includes the production-oriented domain schema, storefront, cart, checkout, customer account, admin operations, marketing, settings, SEO, and demo seeding foundation needed for a complete footwear commerce project.

## Requirements

- PHP 8.3 or newer. Verified locally with PHP 8.3.30.
- Composer 2.9 or newer. Verified locally with Composer 2.9.7.
- Node.js 22 or compatible. Verified locally with Node.js 22.17.1 and npm 10.9.2.
- MySQL 8 or compatible. Verified locally with Laragon MySQL 8.0.30.
- Laravel 13. Verified locally with Laravel Framework 13.24.0.

## Installation

Create the local databases:

```sql
CREATE DATABASE amarvero CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE amarvero_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Install dependencies and prepare the app:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

On Windows, use `copy .env.example .env` instead of `cp .env.example .env`.

## Environment

Core local values:

```dotenv
APP_NAME=Amarvero
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=amarvero
DB_USERNAME=root
DB_PASSWORD=
```

Demo admin creation reads from:

```dotenv
ADMIN_NAME=
ADMIN_EMAIL=
ADMIN_PASSWORD=
```

For local and test environments, blank admin credentials seed `admin@example.test` with password `password`. Production seeding requires `ADMIN_EMAIL` and `ADMIN_PASSWORD`.

Do not store payment, shipping, mail, analytics, or other provider secrets in admin-editable settings. Keep secrets in environment variables or secure deployment configuration.

## Development

Run the full starter development stack:

```bash
composer run dev
```

Or run pieces separately:

```bash
php artisan serve
php artisan queue:listen
npm run dev
```

Build production assets:

```bash
npm run build
```

Run checks:

```bash
composer validate
php artisan optimize:clear
php artisan route:list
php artisan migrate:status
php artisan test --compact
vendor\bin\pint --format agent
npm run build
```

## Architecture

- Authentication: Laravel Fortify starter-kit routes and Livewire views, including registration, login, password reset, email verification, password confirmation, two-factor authentication, and passkeys.
- Frontend: Blade, Livewire 4, Flux, Tailwind CSS 4, Vite, and Alpine managed by the Livewire stack.
- Authorization: Spatie Laravel Permission with custom middleware aliases, `HasRoles` on `App\Models\User`, and seeded role/permission foundations.
- Database: MySQL for local development and MySQL for tests through the `amarvero_testing` database.
- Media: Laravel Storage with the public storage link; user uploads should be stored under `storage/app/public` using validated randomized filenames.
- Payments: implement through a small provider interface in later phases; never trust browser-submitted totals.
- Shipping: implement through service/provider interfaces in later phases so courier integrations can be swapped safely.

## Installed Packages

- `laravel/framework`: Laravel application framework.
- `laravel/fortify`: authentication backend and security flows.
- `livewire/livewire`, `livewire/flux`, `livewire/blaze`: reactive UI and starter-kit components.
- `laravel/boost`: Laravel AI guidance and inspection support.
- `laravel/pint`, `pestphp/pest`, `pestphp/pest-plugin-laravel`, `larastan/larastan`: formatting, testing, and static analysis.
- `spatie/laravel-permission`: role and permission management for admin authorization.
- `swiper`: storefront and product-gallery sliders planned for later phases.
- `tailwindcss`, `@tailwindcss/vite`, `vite`, `laravel-vite-plugin`: Tailwind CSS 4 and asset bundling.

## Admin Roles

The Phase 0 seed creates:

- Super Admin
- Admin
- Product Manager
- Order Manager
- Content Manager
- Customer Support

Super Admin receives every seeded permission. Other roles receive module-appropriate permissions for catalog, orders, content, support, or settings workflows.

## Phase 0 Log

- Verified PHP, Composer, Node, npm, Laravel 13, Livewire, Fortify, Boost, Tailwind CSS 4, and Vite.
- Switched local environment from SQLite to MySQL because SQLite PDO was unavailable and the project requires MySQL.
- Created `amarvero` and `amarvero_testing` databases.
- Ran starter migrations on MySQL.
- Published and migrated Spatie Permission config and tables.
- Added Spatie middleware aliases and `HasRoles` to `App\Models\User`.
- Added `RolePermissionSeeder` and a Pest feature test for seeded authorization behavior.
- Installed Swiper for upcoming dynamic storefront sliders.
- Linked public storage.

## Phase 1 Log

- Added the grouped `create_ecommerce_domain_tables` migration for the e-commerce domain foundation.
- Added catalog tables for brands, nested categories, product collections, attributes, attribute values, products, product variants, product images, inventory movements, and size guides.
- Added commerce tables for customer addresses, carts, cart items, wishlists, wishlist items, coupons, coupon restrictions, coupon redemptions, campaigns, shipping zones, and shipping methods.
- Added order tables for orders, order-address snapshots, order-item snapshots, payments, status timelines, payment timelines, notes, shipments, returns, return items, refunds, and product reviews.
- Added content and settings tables for announcement bars, navigation menus, hero slides, homepage sections, promotional banners, static pages, FAQs, testimonials, store locations, service benefits, newsletter subscribers, site settings, social links, and footer links.
- Added backed enums under `App\Enums` for product, cart, coupon, discount, order, payment, shipment, return, refund, review, content, inventory movement, and address statuses/types.
- Added Eloquent relationships and casts across the domain models, including customer relationships on `App\Models\User`.
- Added `EcommerceDomainSchemaTest` to verify Phase 1 tables, casts, and relationship persistence across catalog and commerce flows.

## Phase 2 Log

- Added `App\Support\AdminPermissions` as the single source of truth for admin roles, permission groups, and role assignments.
- Updated `RolePermissionSeeder` to seed all permissions and roles from the centralized matrix.
- Added `EnsureAdminAccess` middleware and registered the `admin` middleware alias.
- Added a Super Admin `Gate::before` override and policy discovery for `App\Models` to `App\Policies`.
- Added a shared `AdminPolicy` base class for admin CRUD and special actions such as review moderation, order status changes, payment updates, refunds, exports, and invoice printing.
- Added model policies for the Phase 1 catalog, customer, cart, wishlist, coupon, order, payment, return, refund, review, content, shipping, settings, footer, and social-link models.
- Added `AdminAuthorizationTest` to verify admin middleware access, role boundaries, policy decisions, and Super Admin override behavior.

## Phase 3 Log

- Added reusable Blade design-system components for brand lockups, containers, buttons, badges, section headings, empty states, statistic cards, and admin navigation links.
- Replaced the starter welcome page with an original responsive storefront shell and a generated unbranded footwear hero image stored at `public/images/storefront/hero-footwear.png`.
- Replaced the starter authenticated dashboard placeholders with real account metrics backed by the signed-in user's related orders, addresses, wishlists, and reviews.
- Added a custom authorized admin layout with responsive navigation, account controls, breadcrumbs, skip links, and an `/admin` overview route.
- Added `App\Http\Controllers\Admin\DashboardController` to render real database counts for products, published products, orders, paid revenue, customers, low-stock variants, catalog foundations, and recent orders.
- Added `PhaseThreeLayoutTest` to verify the storefront shell, customer dashboard, and protected admin overview behavior.

## Phase 4 Log

- Added class-based Livewire admin catalog components for brand, category, collection, attribute/value, and product management under `App\Livewire\Admin\Catalog`.
- Added searchable, filterable, paginated admin catalog screens under `/admin/catalog/brands`, `/admin/catalog/categories`, `/admin/catalog/collections`, `/admin/catalog/attributes`, and `/admin/catalog/products`.
- Added policy-protected create, update, and delete actions for brands, nested categories, scheduled collections, attributes, scoped attribute values, and products.
- Added product editing for publication status, scheduled publishing, pricing, sale windows, merchandising flags, SEO fields, brand assignment, categories, collections, and attribute values.
- Added safe bulk product status updates for draft, published, and archived product states.
- Added shared catalog form normalization helpers for slugs, nullable fields, booleans, IDs, decimals, and datetime-local inputs.
- Updated the custom admin layout with catalog navigation that is shown according to role permissions.
- Added `AdminCatalogManagementTest` to verify catalog route protection, CRUD behavior, authorization boundaries, attribute values, product relationships, validation, and bulk status updates.

## Phase 5 Log

- Added Livewire admin catalog screens for product variants, product images, inventory, and size guides under `/admin/catalog/variants`, `/admin/catalog/images`, `/admin/catalog/inventory`, and `/admin/catalog/size-guides`.
- Added manual variant CRUD with SKU generation, product-option validation, duplicate combination prevention, SKU-level pricing fields, dimensions, stock settings, active status, and attribute value assignment.
- Added generated variant creation from each product's assigned active variant-option attribute values, with a safe limit and duplicate-combination skipping.
- Added product image upload through the public storage disk, variant-specific images, primary-image enforcement, alt text, and image ordering.
- Added audited inventory adjustment workflows, including bulk stock updates and `InventoryMovement` rows recorded through `App\Support\Inventory\AdjustVariantInventory`.
- Added size-guide management with brand/category scoping, product assignment, active status, rich content, and normalized measurement rows.
- Updated admin catalog navigation with Phase 5 modules guarded by existing granular permissions.
- Added `AdminCatalogOperationsTest` to verify Phase 5 routes, variant generation, image upload and primary image behavior, inventory audit movements, size-guide assignment, and authorization boundaries.

## Phase 6 Log

- Added admin content screens for announcement bars, navigation menus/items, homepage slides/sections/banners, CMS library records, footer sections/links, and social links under `/admin/content/*`.
- Added reusable content-form helpers for scheduled content, slugs, nullable values, booleans, datetime inputs, ID lists, and key-value JSON content.
- Replaced the static home route with `App\Http\Controllers\StorefrontController` and `App\Support\Storefront\StorefrontContent` for active scheduled storefront content.
- Updated the storefront layout to render the active announcement bar, database-managed primary navigation, footer sections, and social links with safe fallbacks.
- Updated the homepage to render published hero slides, promotional banners, homepage sections, service benefits, testimonials, and store locations from the database.
- Added public CMS page rendering at `/pages/{slug}` for published static pages.
- Added `PhaseSixContentTest` to verify admin authorization, CMS CRUD flows, scheduled storefront rendering, dynamic navigation/footer output, and public page visibility.

## Phase 7 Log

- Added `App\Support\Storefront\ProductCatalog` for optimized published product queries, active sale pricing, searchable filters, stock labels, media URLs, variant payloads, review summaries, and related product discovery.
- Added public Livewire listing routes for `/shop`, `/search`, `/sale`, `/featured`, `/new-arrivals`, `/best-sellers`, gender pages, category pages, brand pages, and collection pages.
- Added the responsive product listing experience with debounced search, sorting, pagination, mobile and desktop filters, active filter chips, grid/list views, sale badges, color swatches, and stock labels.
- Added public product detail pages at `/products/{slug}` with gallery media, variant option selection, selected SKU/stock/price state, product details, care notes, size guides, approved reviews, rating distribution, and related products.
- Updated storefront navigation fallbacks and CMS link generation so category and collection menu items resolve through named storefront routes.
- Added `PhaseSevenStorefrontTest` to verify public catalog route contexts, Livewire search/filter behavior, published visibility, active sale windows, variant selection, size guides, reviews, related products, and draft product 404 behavior.

## Phase 8 Log

- Added `App\Support\Cart\CartManager` for guest and customer active carts, server-side price snapshots, stock/backorder validation, quantity limits, cart summaries, item updates, clearing, and guest-to-customer merge logic.
- Added `App\Support\Cart\WishlistManager` for authenticated default wishlists, saved product/variant validation, item counts, item removal, and wishlist-to-cart workflows.
- Added public cart routes and controller-backed add-to-cart endpoints, plus authenticated wishlist routes and save-to-wishlist endpoints.
- Added a login event listener that merges the current guest session cart into the authenticated customer cart while preserving server-side quantity rules.
- Added responsive cart and wishlist Livewire pages with real database-backed items, quantity controls, removal, cart subtotal, move-to-cart, and empty states.
- Updated product detail pages and product cards with functional add-to-cart and wishlist actions.
- Updated storefront navigation with real cart and wishlist counts.
- Added `PhaseEightCartWishlistTest` to verify guest cart writes, server-side pricing/stock validation, cart quantity updates, wishlist save/remove/move behavior, protected wishlist access, and guest cart merging on login.

## Phase 9 Log

- Added `App\Support\Checkout\CouponValidator` for active-window checks, minimum order validation, usage limits, first-order/customer eligibility, scoped coupon restrictions, fixed/percentage discounts, and free-shipping coupon support.
- Added `App\Support\Checkout\ShippingRateResolver` for active zone/method matching by country and region, free-shipping thresholds, and coupon-driven shipping discounts.
- Added `App\Support\Checkout\CheckoutManager` to recalculate checkout totals server-side, refresh line prices, lock stock-sensitive variants, create order/address/item/payment snapshots, record status and payment events, redeem coupons, write sale inventory movements, and convert carts atomically.
- Added public checkout and session-scoped order confirmation routes with a responsive Livewire checkout page for contact, delivery, shipping, payment, coupon, order note, and summary workflows.
- Updated the cart summary with a checkout call to action.
- Made coupon pivot relationships explicit so model relationships match the existing `coupon_*` table names.
- Added `PhaseNineCheckoutTest` to verify coupon application, shipping/payment/order creation, inventory deduction, cart conversion, invalid coupon rejection, stale stock blocking, and guest confirmation access.

## Phase 10 Log

- Added authenticated customer account routes for address book, order history, order details, and product reviews under `/account/*`.
- Added `App\Livewire\Account\AddressBook` with customer-owned address CRUD, billing/shipping address types, and single-default shipping/billing enforcement.
- Added `App\Livewire\Account\OrderHistory` and `App\Livewire\Account\OrderDetail` for customer-scoped order browsing, status filtering, item snapshots, payment summaries, shipping snapshots, and order timeline visibility.
- Added `App\Livewire\Account\ReviewManager` so customers can submit, edit, and delete verified-purchase reviews for products from their own orders, with submissions reset to pending moderation.
- Updated the signed-in account dashboard with recent orders, default shipping address, pending-review visibility, and shortcuts into orders, addresses, reviews, and settings.
- Replaced starter account navigation links with customer account, wishlist, shop, and settings links across sidebar, header, mobile, and user-menu layouts.
- Added `PhaseTenCustomerAccountTest` to verify route protection, order ownership, address defaults, verified-purchase review workflows, and cross-account review blocking.

## Phase 11 Log

- Added admin operations routes for orders and customers under `/admin/operations/*`.
- Added `App\Livewire\Admin\Operations\OrderIndex` with order search, fulfillment/payment filters, status updates, payment updates, support notes, item review, and audit timeline visibility.
- Added `App\Support\Admin\AdminOrderManager` to centralize admin order status events, payment events, and order notes.
- Added `App\Livewire\Admin\Operations\CustomerIndex` for roleless customer search, profile updates, order summaries, address review, review counts, and wishlist activity.
- Added admin marketing under `/admin/marketing` for campaigns, coupons, coupon usage, newsletter subscriber moderation, and featured product merchandising.
- Added `App\Support\Settings\SettingsManager` and `/admin/settings/store` for brand, contact, SEO, analytics placeholder, maintenance, newsletter, invoice, order-rule, review, and non-secret payment setting persistence.
- Added shipping zone and shipping method management to the settings screen for checkout delivery configuration.
- Updated the admin layout with permission-aware Operations, Marketing, and Settings navigation.
- Added `PhaseElevenAdminOperationsTest` to verify Phase 11 routes, order audit writes, customer profile edits, marketing workflows, settings persistence, and shipping-rate setup.

## Phase 12 Log

- Added `App\Support\Seo\SeoManager` for settings-backed titles, descriptions, canonical URLs, robots directives, Open Graph/Twitter tags, favicon overrides, and JSON-LD payloads.
- Added Organization, WebSite, BreadcrumbList, Product, Offer, and real AggregateRating structured data for storefront, listing, CMS, and product detail pages.
- Added `/sitemap.xml` and `/robots.txt` routes backed by native Laravel controllers without adding an SEO package.
- Added sitemap coverage for public storefront routes, active categories, active brands, active collections, published products, and published CMS pages while excluding draft/private surfaces.
- Added noindex defaults for admin, auth, and customer account layouts, plus noindex filtered/search listing variants with clean canonical URLs.
- Cached defined site settings and invalidated that cache on settings saves to reduce repeated SEO/settings queries.
- Added async image decoding and breadcrumb `aria-current` refinements to key storefront templates.
- Added `PhaseTwelveSeoTest` to verify metadata, structured data, listing robots behavior, sitemap XML, and robots.txt.

## Phase 13 Log

- Added `AdminUserSeeder` for environment-backed Super Admin creation with local/testing fallback credentials and a production guard.
- Added `EcommerceDemoSeeder` with idempotent demo settings, brands, categories, collections, attributes, size guide, products, variants, images, inventory movements, shipping, campaign/coupon/newsletter records, CMS content, customer account data, cart, wishlist, order, payment, shipment, return, refund, coupon redemption, and review records.
- Updated `DatabaseSeeder` to seed roles/permissions, the admin user, and the complete demo e-commerce dataset.
- Added factories for brands, categories, products, product variants, and orders, plus `HasFactory` support on those models.
- Added `PhaseThirteenSeedersTest` to verify factory persistence, seeded admin credentials/roles, idempotent dataset counts, order-operation records, and seeded storefront/sitemap visibility.
- Documented local seeding expectations and final QA commands.

## Phase 14 Log

- Expanded the admin dashboard into a period-filtered operations dashboard with validated presets and custom date ranges.
- Added `App\Support\Admin\AdminDashboardMetrics` to keep revenue, net sales, AOV, customer, order-status, catalog, sales-leader, coupon, stock-risk, and recent-order queries out of the controller.
- Added current-versus-previous-period trend labels for KPI cards.
- Added best-selling products, top categories, top brands, coupon usage, order pipeline, and inventory watchlist sections to the dashboard.
- Added `DashboardOverviewRequest` authorization and validation for dashboard filters.
- Added `PhaseFourteenDashboardTest` to verify dashboard analytics rendering and custom date validation.

## Phase 15 Log

- Hardened the custom admin shell with Escape-to-close mobile navigation, focus restoration, explicit menu state attributes, body scroll locking while the drawer is open, and a permission-aware quick navigation search.
- Added the reusable `x-admin.table-region` Blade component for focusable, accessible horizontally scrollable admin tables with mobile scroll hints.
- Applied accessible table regions to the product and order admin tables, plus the admin dashboard recent-orders table.
- Added `PhaseFifteenAdminUxTest` to verify the admin shell navigation affordances and scroll-region semantics.

## Phase 16 Log

- Added web security headers for content sniffing protection, frame restrictions, referrer policy, permissions policy, and HTTPS-only HSTS.
- Added centralized security rate-limit keys and a reusable guard for validation-friendly Livewire throttling.
- Registered named rate limiters for storefront search, cart writes, wishlist writes, checkout, and admin requests.
- Applied route throttles to security-sensitive storefront and admin surfaces.
- Rate-limited checkout coupon attempts and customer review submissions per shopper context.
- Added `PhaseSixteenSecurityTest` to verify headers, route throttles, coupon throttling, and review throttling.

## Phase 17 Log

- Added queued transactional order email infrastructure with reusable order notification dispatch.
- Added customer order confirmation emails after successful checkout transactions.
- Added customer order status update emails when admins move orders through the operations workflow.
- Added Markdown order email templates with order numbers, status, payment state, shipping method, totals, and item snapshots.
- Added `PhaseSeventeenOrderCommunicationTest` to verify checkout mail dispatch, admin status mail dispatch, note-only suppression, and rendered email content.

## Phase 18 Log

- Split demo data into explicit section seeders for store settings, catalog, marketing, storefront content, and customer/order experience.
- Updated `DatabaseSeeder` to call each demo section seeder directly after roles and the admin user.
- Expanded seeded storefront content with multiple hero slides, homepage blocks, promotional banners, benefits, testimonials, store locations, social links, static support pages, and footer groups.
- Added richer navigation, footer, FAQ, size-guide, and care-guide records so seeded public pages no longer fall back to the sparse empty storefront.
- Added `PhaseEighteenDemoSectionSeedersTest` to verify the section seeders populate every visible storefront area.

## Phase 19 Log

- Added a storefront-only noir color theme inspired by black layered contour imagery.
- Added a fixed subtle contour texture, smoky charcoal surfaces, low-contrast borders, muted text, and warm ivory customer CTAs.
- Scoped the theme to `components.layouts.storefront` so admin screens keep their existing operational styling.
- Applied theme hooks to the storefront shell, header, footer, homepage hero, promotional bands, cards, forms, and hero media.
- Added `PhaseNineteenStorefrontThemeTest` to verify the user-side theme shell and CSS hooks remain in place.

## Deployment Notes

Use a production MySQL database, queue worker, configured mail transport, storage disk, and real environment secrets. Laravel Cloud is a suitable deployment option for Laravel applications. Run `php artisan config:cache`, `php artisan route:cache`, a queue worker, and `npm run build` as part of production deployment.

## Security Notes

Validate every write server-side, authorize admin actions with roles, permissions, policies, and middleware, keep secrets out of the database-backed settings UI, use CSRF protection, preserve Fortify throttling, and recalculate prices, discounts, stock, shipping, and totals on the server.
