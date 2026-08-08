# Amarvero

Amarvero is a Laravel 13 footwear e-commerce application built on the official Livewire starter kit. Phase 0 has prepared the project for production e-commerce work by validating the runtime, moving the app and tests to MySQL, installing the required role/permission and slider dependencies, and confirming the starter authentication, Livewire, Tailwind CSS 4, Vite, tests, and build pipeline work.

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

Demo admin creation in later phases must read from:

```dotenv
ADMIN_NAME=
ADMIN_EMAIL=
ADMIN_PASSWORD=
```

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

## Deployment Notes

Use a production MySQL database, queue worker, configured mail transport, storage disk, and real environment secrets. Laravel Cloud is a suitable deployment option for Laravel applications. Run `php artisan config:cache`, `php artisan route:cache`, a queue worker, and `npm run build` as part of production deployment.

## Security Notes

Validate every write server-side, authorize admin actions with roles, permissions, policies, and middleware, keep secrets out of the database-backed settings UI, use CSRF protection, preserve Fortify throttling, and recalculate prices, discounts, stock, shipping, and totals on the server.
