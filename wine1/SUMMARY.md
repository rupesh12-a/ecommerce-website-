# Vino e Casa — Wine Shop: Project Summary

A classic **PHP + MySQL (PDO)** e-commerce site for a wine shop. No framework, no Composer,
no build step — plain PHP files served by Apache (XAMPP/WAMP) or PHP's built-in server.

---

## 1. How to run the project

### Requirements
- PHP 8.0+ (with `pdo_mysql` enabled)
- MySQL 5.7+ / MariaDB 10+
- Apache (with `mod_rewrite`) **or** the PHP built-in dev server

### Step 1 — Create and load the database
```bash
mysql -u root -p < database/schema.sql   # creates the `wine_shop` DB + tables
mysql -u root -p < database/seed.sql     # sample categories, 16 products, users, settings
```
> `schema.sql` drops and recreates all tables — run it only on a fresh/empty database.

### Step 2 — Configure the connection (optional)
Credentials live in `config/config.php` and default to XAMPP defaults
(`root` / empty password / `wine_shop` on `127.0.0.1:3306`).
Override via environment variables if needed:

```
WINE_DB_HOST, WINE_DB_PORT, WINE_DB_NAME, WINE_DB_USER, WINE_DB_PASS
```

### Step 3 — Serve the app

**Option A — Apache/XAMPP:** copy the folder into `htdocs/` and open
`http://localhost/wine/` (the `.htaccess` files handle routing/security).

**Option B — PHP built-in server:**
```bash
php -S localhost:8000
```
then open `http://localhost:8000/`.
`config/config.php` auto-detects the base URL, so links work in both setups.

### Step 4 — Sign in
- **Storefront:** register at `register.php`
- **Admin panel:** `admin/login.php` — use the seeded admin account from `seed.sql`
  (email/password are defined there; password hashes are `password_hash()` bcrypt values).

---

## 2. How the application works (architecture)

Every page follows the same request lifecycle:

```
page.php
  └─ require config/config.php
        ├─ defines DB_* constants, BASE_URL, APP_ROOT
        ├─ starts the session (httponly, SameSite=Lax)
        ├─ require includes/functions.php   (helpers: auth, cart, CSRF, money…)
        ├─ require includes/database.php    (db() → shared PDO connection)
        └─ $pdo = db()
  └─ run page logic (query DB, handle POST)
  └─ require includes/header.php   (opens <html>, nav, flash toasts, <main>)
  └─ render HTML
  └─ require includes/footer.php   (footer, window.APP = {base, csrf}, main.js)
```

Key mechanics:

| Concern | Implementation |
|---|---|
| **Routing** | Plain file-based URLs (`shop.php?category=…`). `.htaccess` sets `index.php` as directory index, serves `404.php`, and blocks `/config`, `/includes`, `/database`. |
| **Database** | Single lazily-created PDO connection via `db()` in `includes/database.php`. Prepared statements everywhere (no SQL injection). |
| **Auth** | Session-based. `require_login()` / `require_admin()` guards; passwords via `password_hash()` / `password_verify()`; `session_regenerate_id(true)` on login/logout. Roles: `customer`, `admin` (table `users`). |
| **CSRF** | Token stored in session; hidden `csrf_field()` in forms, `require_csrf()` on every POST (returns HTTP 419 / JSON for AJAX). |
| **Cart** | Stored in `$_SESSION['cart']` as `[product_id => qty]`. `cart.php` handles `add/update/remove/clear` and returns **JSON when the request is AJAX** (`X-Requested-With`), so `main.js` can update the UI without a reload. |
| **Checkout** | `checkout.php` validates the shipping form, then `create_order()` runs a **transaction**: insert `orders` → insert `order_items` → decrement `products.stock` → commit (rollback on error). |
| **Settings** | Key/value rows in `settings`, read lazily by `setting()` (shop name, currency, shipping fee, free-shipping threshold, GST %). |
| **Flash messages** | `flash()` writes to `$_SESSION['flash']`; `header.php` renders them as toasts via `take_flashes()`. |
| **AJAX API** | `api/products.php` returns JSON live-search results (min 2 chars, max 6 rows). |
| **Base URL** | Computed at bootstrap so `url()` / `asset()` work from any subdirectory and from `admin/` or `api/` subfolders. |

**Data model** (see `database/schema.sql`):
`categories` 1—∞ `products`; `users` 1—∞ `orders` 1—∞ `order_items`;
`products` 1—∞ `reviews`; `contact_messages`; `settings`.

---

## 3. File-by-file reference

### Root — storefront pages

| File | Description |
|---|---|
| `.htaccess` | Apache config: disables directory listing, sets `DirectoryIndex index.php`, routes 404s to `/404.php`, blocks direct access to `config/`, `includes/`, `database/`. |
| `index.php` | Homepage — hero, trust badges, category grid, featured products, sale promo, new arrivals, newsletter block. Queries categories/featured/new/sale products. |
| `shop.php` | Product catalogue with search (`q`), category filter, sale filter, price min/max, 5 sort options and pagination (9 per page). Renders filter sidebar + product grid. |
| `product.php` | Product detail page by `?slug=`: gallery, price, stock, description, add-to-cart form, related products, and a **review form** (POST inserts into `reviews` for logged-in users). 404 fallback if slug not found/inactive. |
| `cart.php` | Cart controller + view. POST `action` = `add` / `update` / `remove` / `clear` (CSRF-protected, stock-clamped). Returns JSON for AJAX requests, full HTML page otherwise. Shows line items and totals. |
| `checkout.php` | Guest-friendly checkout. Prefills address from the logged-in user, validates fields, calls `create_order()`, stores `$_SESSION['last_order']`, clears the cart, redirects to `order-success.php`. Payment methods: `cod`, `card`, `upi` (mock — no gateway). |
| `order-success.php` | Confirmation page. Only visible to the person who placed the order (`$_SESSION['last_order']`), the owning account, or an admin. |
| `my-orders.php` | Requires login. Lists the current user's orders with their line items and status badges. |
| `login.php` | Customer sign-in (email + password, `password_verify`), regenerates session ID, honours `redirect_after_login`. |
| `register.php` | New customer signup — validates name/email/password/confirmation, checks duplicate email, stores a bcrypt hash with role `customer`. |
| `logout.php` | Destroys `user_id` from the session, regenerates the session ID, redirects home. |
| `about.php` | Static "Our story" marketing page (philosophy, values, team, stats). |
| `contact.php` | Contact form — validates input and inserts into `contact_messages` (read by the admin under Messages). |
| `404.php` | Custom error page (wired up by `.htaccess` `ErrorDocument 404`). |

### `config/`

| File | Description |
|---|---|
| `config.php` | **The bootstrap file every page includes.** Defines DB constants (env-overridable), `APP_ROOT`, auto-detects `BASE_URL`, starts the session, sets error reporting, loads `functions.php` + `database.php`, and creates `$pdo`. |
| `.htaccess` | `Require all denied` — blocks HTTP access to the config directory. |

### `includes/`

| File | Description |
|---|---|
| `functions.php` | All shared helpers: output escaping `e()`, `url()`/`asset()`/`product_image()`, `redirect()`, `json_response()`, `slugify()`; settings (`setting()`, `money()`, `currency()`); product helpers (`product_price()`, `product_discount()`, `star_rating()`); flash messages; **CSRF** (`csrf_token/field/verify/require_csrf`); **auth** (`current_user()`, `require_login()`, `require_admin()`, `is_admin()`); **cart** (`cart_*`, `cart_items()`, `cart_totals()`); `create_order()` transactional checkout; order status helpers. |
| `database.php` | `db()` — one shared PDO connection (exceptions on, assoc fetch, no emulated prepares). Shows a friendly 500 page/JSON if MySQL is unreachable. |
| `header.php` | Storefront layout head: `<head>` with fonts + `style.css`, announcement bar, sticky header (mobile nav toggle, brand, nav links, live search box, account dropdown, cart badge), and flash toasts. Accepts optional `$pageTitle` / `$activePage`. |
| `footer.php` | Closes `</main>`, renders the site footer (brand, link columns, contact info, copyright), injects `window.APP = { base, csrf }`, and loads `assets/js/main.js`. |
| `product-card.php` | Reusable partial rendering one product card (image, sale/out-of-stock pills, meta, price, AJAX add-to-cart form). Expects `$product`. |
| `.htaccess` | `Require all denied`. |

### `admin/` — back office (all pages guarded by `require_admin()`)

| File | Description |
|---|---|
| `includes/layout.php` | Admin shell: `admin_header($title, $active)` / `admin_footer()` output the sidebar + topbar layout, define `admin_nav()`, show pending-order and unread-message badges, and call `require_admin()`. |
| `login.php` | Admin sign-in — verifies password **and** that `role = 'admin'`, otherwise rejects. |
| `logout.php` | Signs out of the admin panel. |
| `index.php` | Dashboard — revenue, order/product/customer counts, low-stock alert, recent orders, top-selling products, and orders-by-status chart. |
| `products.php` | Product list with search/filter/pagination and bulk POST actions: delete, toggle active, toggle featured, quick stock update. |
| `product-form.php` | Create/edit product (name, category, description, price, sale price, stock, vintage, region, grape, alcohol, image, featured, active) with validation and optional file/image path handling. |
| `categories.php` | Add/edit/delete categories; auto-generates unique slugs via `slugify()`. |
| `orders.php` | Order list with status + text filters, pagination, and inline status update. |
| `order-view.php` | Single order detail: customer info, line items, and a form to update status + internal notes. |
| `customers.php` | User list with search; delete accounts and change roles (cannot delete/demote yourself). |
| `messages.php` | Inbox for `contact_messages` — mark read/unread, delete, unread filter. |
| `settings.php` | Edit `settings` rows (shop name, tagline, email, phone, address, currency, shipping fee, free-shipping threshold, GST %) + change the admin password. |
| `.htaccess` (via root) | Admin pages are under the root rules; layout enforces the auth check. |

### `api/`

| File | Description |
|---|---|
| `products.php` | JSON endpoint for the header live-search: `?q=` (≥2 chars) returns up to 6 active products matching name/grape/region, with formatted price, image and URL. |

### `assets/`

| Path | Description |
|---|---|
| `css/style.css` | Full storefront design system (531 lines): CSS variables, typography (Cormorant Garamond + Inter), header/hero/grid/product-card/cart/checkout/footer/responsive breakpoints. |
| `css/admin.css` | Admin panel styles: sidebar layout, stat cards, tables, forms, badges. |
| `js/main.js` | Storefront behaviour (208 lines): toast notifications, sticky header, mobile nav + dropdowns, filter panel toggle, cart quantity steppers, **AJAX add/update/remove/clear cart** (updates badge, line totals and summary in place), and **live search** hitting `api/products.php`. |
| `js/admin.js` | Admin behaviour: mobile sidebar toggle, `data-confirm` destructive-form confirmations, product image live preview. |
| `img/logo.svg`, `img/wine-01…16.svg` | Brand logo and the 16 product bottle illustrations referenced by the seed data. |
| `img/uploads/` | Empty folder (with `.gitkeep`) for admin-uploaded product images. |

### `database/`

| File | Description |
|---|---|
| `schema.sql` | Creates the `wine_shop` database and all 8 tables (`categories`, `products`, `users`, `orders`, `order_items`, `reviews`, `contact_messages`, `settings`) with foreign keys and indexes. Drops existing tables first. |
| `seed.sql` | Demo data: 5 categories, 16 products, customer/admin users (bcrypt password hashes), sample orders, reviews, contact messages, and store settings. |
| `.htaccess` | `Require all denied` — prevents downloads of SQL files. |

---

## 4. Quick map of user flows

- **Browse → buy:** `index.php` → `shop.php` → `product.php` → `cart.php` → `checkout.php` → `order-success.php`
- **Account:** `register.php` / `login.php` → `my-orders.php` → `logout.php`
- **Admin:** `admin/login.php` → `admin/index.php` → products / categories / orders / customers / messages / settings
- **Search:** header input → `api/products.php` (AJAX) → click result → `product.php?slug=…`
