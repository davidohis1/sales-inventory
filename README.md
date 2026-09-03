# Sales, Inventory & Business Management System (Multi-Tenant)

A complete CorePHP (no framework) + MySQL + JWT multi-tenant sales, inventory,
and business management system. Backend API and server-rendered frontend run
from a single entrypoint.

## Stack

- **CorePHP** — plain PHP 8.1+, no Laravel/Symfony. A tiny hand-rolled PSR-4
  autoloader (`vendor/autoload.php`) is included so it runs with **zero
  Composer dependencies**. If you do run `composer install` later, Composer's
  own autoloader will safely take over the same `App\` namespace.
- **MySQL** — raw PDO + prepared statements, no ORM.
- **JWT** — dependency-free HS256 implementation (`app/Core/JWT.php`), access
  + refresh tokens.
- **Vanilla JS** — the admin portal and storefront are both single-page
  apps built with plain `fetch()` + `history.pushState`. No React/Vue, no
  build step, and **no full-page reloads** when switching between modules.

## What's new: Plans, Trials, Billing & Platform Admin

- **Public marketing site**: `/` (landing), `/register` (business signup),
  `/login` (platform-wide login — looks up your business by email, no slug
  needed), `/plans` or `/pricing` (public pricing page).
- **3-day free trial** starts automatically on registration. Every feature
  is unlocked during the trial. Once it (or a paid subscription) expires,
  feature API routes return `402` and the portal sends the user to
  **Plans & Billing** to pay.
- **Three plans** — Basic (₦3,500/mo: POS, Products, Customers, Expenses),
  Advanced (₦5,500/mo: + Online Orders, Store, Reports), Premium (₦7,500/mo:
  + Staff, Branches, AI Insights). Prices, names, descriptions and the
  feature toggles are all editable from the platform admin.
- **Flutterwave** powers both subscription payments and store-order
  checkout (`app/Core/Flutterwave.php`, no SDK dependency). If
  `FLW_SECRET_KEY` is left blank in `.env`, the app automatically falls
  back to a "Simulate Successful Payment" flow so the whole loop is
  testable without live keys.
- **Platform admin** at `/platformadmin` — two pages: **Overview** (business
  counts, 30-day revenue, recent payments, and inline plan/price/feature
  editing) and **Businesses** (every tenant, their plan, color-coded days-
  to-expiry — red at ≤7 days — and a "Send Reminder" button that emails
  them). Separate login from tenant accounts; seeded credentials below.
- **Online order fulfillment**: `Ordered → Accepted → On Delivery →
  Delivered`, with `amount_paid` tracked per order and shown alongside the
  total on the Orders page.
- **Digital Products** — free forever on every plan (not gated by trial or
  subscription status). Sidebar → its own dashboard (revenue/sales,
  date-filterable) → "Add Product" (name, price, compare-at price,
  category, rich-text description, up to 8 images, a video URL, and the
  downloadable file itself). Publishing creates a public checkout page at
  the bare top-level URL `/{product-slug}` — Flutterwave-powered, with the
  download link emailed on successful payment. Withdrawals (5% platform
  fee) go through the same email-to-`PLATFORM_PAYOUT_EMAIL` flow as store
  earnings, processed manually within ~3 hours.
- **Header image bank** — platform admin uploads header/banner photos per
  store category (Store Page → Theme tab on the tenant side); tenants pick
  category → theme → one of the curated photos for their storefront hero,
  or upload their own.

## Getting Started

### 1. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE sales_inventory CHARACTER SET utf8mb4"
mysql -u root -p sales_inventory < database/schema.sql
```

> **Already had this project running before the Store Page update?** Don't
> re-run `schema.sql` (it would wipe your data). Instead run the small
> migration that just adds the new `store_settings` table:
> ```bash
> mysql -u root -p sales_inventory < database/migration_v2.sql
> ```

> **Already had the Store Page (migration_v2) but not the "I Have Paid"
> checkout flow?** Run the follow-up migration:
> ```bash
> mysql -u root -p sales_inventory < database/migration_v3.sql
> ```

> **Already had this project running before Plans, Trials &amp; Billing?**
> Run the v4 migration — it adds `plans`, `plan_features`, `payments`,
> `platform_admins`, subscription/trial columns on `tenants`, and the
> 4-stage order-fulfillment status on `online_orders`:
> ```bash
> mysql -u root -p sales_inventory < database/migration_v4.sql
> ```
> Then run v5 (store/digital-product withdrawals), v6 (the header-image
> bank), and v7 (Digital Products) in order:
> ```bash
> mysql -u root -p sales_inventory < database/migration_v5.sql
> mysql -u root -p sales_inventory < database/migration_v6.sql
> mysql -u root -p sales_inventory < database/migration_v7.sql
> ```

### 2. Configure environment

```bash
cp .env.example .env
# then edit .env with your DB credentials, a random JWT_SECRET,
# and (optionally) a GEMINI_API_KEY for the AI Insights widget.
```

Generate a strong JWT secret:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### 3. Seed demo data

```bash
php database/seed.php
```

This creates a demo tenant (`ajtech`) with 2 branches, 3 staff accounts
(owner/manager/staff — all password `password123`), 6 products, 3 customers,
a couple of expenses, and 2 sample sales — so the app is testable
immediately.

### 4. Run

```bash
php -S localhost:8009 -t public
```

- Public storefront: **http://localhost:8009/ajtech**
- Admin portal: **http://localhost:8009/ajtechportal**
- Root landing page: **http://localhost:8009/**

## URL / Multi-Tenancy Structure

| URL pattern              | Purpose                                   |
|---------------------------|-------------------------------------------|
| `/`                        | Marketing landing page                    |
| `/register`                | Business self-signup (starts 3-day trial) |
| `/login`                   | Platform-wide login (email-only, no slug) |
| `/plans` or `/pricing`     | Public pricing page                       |
| `/payments/callback`       | Flutterwave return URL                    |
| `/platformadmin`           | Platform admin — Overview + plan editor   |
| `/platformadmin/businesses`| Platform admin — business list + reminders|
| `/{slug}`                  | Public online storefront for a tenant     |
| `/{slug}/product/{id}`     | Product detail page                       |
| `/{slug}/cart`             | Shopping cart                             |
| `/{slug}/checkout`         | Checkout / place order                    |
| `/{slug}/receipt/{id}`     | Public, shareable/printable sales receipt |
| `/{slug}portal`            | Admin/staff portal (SPA)                  |
| `/api/{slug}/...`          | JSON API, scoped to that tenant           |
| `/api/auth/...`, `/api/plans`, `/api/payments/...` | Platform-level JSON API (no slug) |
| `/api/platformadmin/...`   | Platform admin JSON API                   |

Every relevant table carries a `tenant_id`, and `AuthMiddleware` cross-checks
the JWT's tenant against the URL slug on every authenticated request, so
tenants are fully isolated at the query level.

## Modules implemented

**MVP (fully working end-to-end):**
1. Dashboard — today's sales/profit, stock value, low/out-of-stock counts, payment method breakdown, best sellers, and a **Quick Sale** button (see below)
2. Products & Inventory — categories, SKU, prices, stock adjustments + history log, low-stock alerts
3. Sales / POS — live product search, cart, discounts, cash/transfer/POS/split/credit, refunds, printable + shareable receipts
4. Profit Tracking — per-product and per-range profit & margin, net profit after expenses
5. Expenses — categorized entries, folded into profit calculations
6. Customers & Debt — profiles, purchase/payment history, outstanding debt, partial payments, credit limits
7. Staff Management — Owner/Manager/Staff roles, permission scoping, **paginated** activity log (who did what)
8. Reports — sales, inventory, profit, staff performance, customers — date-filterable, CSV export
9. Low-Stock Alerts — automatic, surfaced on the dashboard and a dedicated endpoint

**Quick Sale (Dashboard):** a lightweight sale dialog separate from the full POS screen. The customer is always **typed by name** — never chosen from a dropdown. Typing searches existing customers live; picking a suggestion reuses that customer, and checking out with an unmatched name creates a brand-new customer record together with the sale (`Customer::findOrCreateByName`).

**Phase 2 (schema scaffolded + functional):**
- **A. Online Store** — a dedicated **Store Page** in the admin portal (owner/manager only) lets you:
  - Pick from **5 real, distinct storefront templates** — *Aurora* (sidebar-filtered grid), *Wink* (clean product-list with a promo strip), *Luxora* (editorial fashion hero + testimonials), *Marketly* (bold marketplace with a flash-deal banner), *NovaTrend* (lifestyle hero with floating cards) — switchable anytime, saved per tenant.
  - Edit the storefront's text content (announcement bar, hero heading/subheading, promo badges, etc.) per template, each field with a **✨ AI** button that calls Gemini (or a deterministic fallback with no key configured) to draft copy.
  - Manage **shop categories** — the storefront filters by category, name, and price.
  - See every product in stock (with or without images) and manage its store listing: upload **multiple images**, write/AI-generate a **description**, and toggle it on/off the store. Listing is blocked until at least one image exists.
  - Choose a **store type** (fashion/tech/beauty/grocery/general) that drives real decorative stock photography (Unsplash) used in each template's hero/banner sections — product photos themselves always come from your own uploads.
  - All 5 templates are fully responsive (desktop/tablet/mobile) and share one product-loading engine (`store.js`) that supports live search, category filtering, and price-range filtering regardless of which template is active.
- **B. AI Insights** — a widget on the admin dashboard (`app/Controllers/Api/AiController.php`) that gathers the tenant's own sales/stock data and asks **Google Gemini** (`gemini-2.0-flash`) for a plain-language summary (trends, slow movers, low-margin products, restock suggestions). The same Gemini integration also powers the Store Page's content/description AI buttons via `POST /api/{slug}/ai/generate-text`. Fully pluggable: add `GEMINI_API_KEY` to `.env` to activate it; without a key (or if the API call fails), deterministic fallback copy is generated locally instead, so nothing ever dead-ends.
- **C. Multi-Branch** — branches table, per-branch stock, staff branch assignment, and a stock-transfer flow between branches (Branches screen in the portal).

## Real-time search

Product search (POS + inventory + Quick Sale), customer name autocomplete (Quick Sale), and storefront search/category/price filtering all use debounced `fetch()` calls against the JSON API as you type — no full page reloads and no page navigation.

## Money handling

All monetary columns are `DECIMAL(14,2)` (not floats), matching the "no
float rounding errors" requirement. If you'd rather store integer kobo,
multiply by 100 at the model boundary — the schema comments make it a
one-line change.

## Folder structure

```
app/
  Core/         Database (PDO), Env, JWT, Router, Request, Response, Auth, Flutterwave, Mailer, Notifications
  Middleware/   AuthMiddleware, RoleMiddleware, TenantStatusMiddleware, PlatformAdminMiddleware
  Models/       One class per table, raw PDO + prepared statements (+ Plan, Payment, PlatformAdmin)
  Controllers/Api/     One controller per tenant module, returns JSON via Response
  Controllers/Admin/   Platform admin controllers (AdminAuthController, PlatformController)
public/
  index.php     SINGLE ENTRYPOINT — routes marketing site, API, portal, storefront, and platform admin
  assets/       css/js for the admin portal, storefront, and platform admin
                assets/css/themes/  — one CSS file per storefront template
  views/marketing     Landing, register, login, plans/pricing, payment-callback pages
  views/platformadmin SPA shell (layout.php) for the platform admin — platformadmin.js renders the rest
  views/portal  SPA shell (layout.php) — admin.js renders everything else
  views/store   Server-rendered storefront pages (cart/checkout/receipt)
                views/store/themes/ — the 5 storefront homepage templates
                (aurora / wink / luxora / marketly / novatrend), chosen
                per-tenant on the admin Store Page
  uploads/      Product images, organized by tenant_<id>/
database/
  schema.sql    Full MySQL schema (all tables, tenant_id scoping, images table, plans/billing)
  seed.php      Demo data seeder (run with `php database/seed.php`) — also seeds the platform admin login
  migration_v2.sql / migration_v3.sql / migration_v4.sql   Incremental migrations for existing installs
storage/
  mail_log.txt  Fallback log of emails when MAIL_LOG_ONLY=true or mail() fails
vendor/
  autoload.php  Minimal dependency-free PSR-4 autoloader for App\
```

## Email notifications

Uses PHP's built-in `mail()` — no external library needed. Since most local
setups (plain `php -S`, XAMPP) don't have a real mail server configured,
set `MAIL_LOG_ONLY=true` in `.env` (the default) and every email is written
to `storage/mail_log.txt` instead of being silently lost, so you can see
exactly what would have been sent. Flip it to `false` once you have real
SMTP/mail working on your server.

Emails are sent for:
- **Every completed sale** (POS or Quick Sale) — admin gets a notification.
- **Every online order placed** — admin gets notified; the customer gets a confirmation (email is now required at checkout).
- **An online order marked "delivered"** — the customer gets a payment/delivery-confirmed email (also fires at "accepted" and "on_delivery").
- **A customer clicking "I Have Paid"** on a bank-transfer checkout — the admin gets a "please verify" email.

The admin notification address is whatever's set on the Store Page's
**Checkout & Notifications** tab, falling back to the tenant owner's login
email if left blank.

## Base path (fixes broken images/links under a subfolder deployment)

If you deploy by copying this whole project into an Apache/XAMPP `htdocs`
folder (rather than pointing the vhost's document root at `public/`), every
absolute link (`/assets/...`, `/uploads/...`, `/api/...`) will 404 because
the browser is missing your project's folder name in the URL. Fix this by
setting, in `.env`:
```
APP_BASE_PATH=/your-folder-name/public
```
Leave it blank if you're running the documented `php -S localhost:8009 -t public` —
that setup needs no base path at all.

## Online store checkout: how orders get completed

On the Store Page's **Checkout & Notifications** tab, pick one of three
completion methods per tenant:
- **Email Only** — the order is placed, both admin and customer get emailed. Simplest.
- **WhatsApp** — after checkout, the customer sees a click-to-chat WhatsApp button (pre-filled with their order number) to finish arranging payment/delivery with you directly.
- **Bank Transfer** — after checkout, the customer sees your bank account details (set on the same tab) and a big **"I Have Paid"** button. Clicking it doesn't auto-confirm the order — it emails you to verify the transfer, then you mark the order "fulfilled" from Online Orders, which emails the customer a payment confirmation.

In every case the order is still recorded and stock is still reserved at
checkout time — the channel only changes what the customer sees next and
how you're notified.

## Storefront branding

On the Store Page's **Branding** tab, upload a logo and an optional hero
banner image (overrides the default stock photo), set a WhatsApp number
(shown as a floating chat button on the storefront), and add social media
links (shown in the shared footer, which also lists categories and a cart
link — present on all 5 templates).

## Notes & next steps

- File uploads are written to `public/uploads/tenant_{id}/`; make sure that
  directory is writable by the PHP process in production.
- The CSV report export doubles as the "exportable to PDF/Excel" requirement
  in a dependency-free way; wiring in `dompdf` or `phpspreadsheet` later is a
  drop-in addition to `ReportController::export()` if true PDF/XLSX output is
  needed.
- `GEMINI_API_KEY` is read from `.env`; the AI Insights endpoint is
  `GET /api/{slug}/ai/insights` and is safe to call repeatedly (each call is
  logged to the `ai_insights` table for history).
