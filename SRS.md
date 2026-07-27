# SRS.md — Software Requirements Specification
## Project: [YOUR_PROJECT_NAME] — Multi-Vendor E-Commerce Marketplace (Daraz-class)

> Fill in `[YOUR_PROJECT_NAME]` and any bracketed placeholder before use.
> This SRS is the single source of truth. Gemini CLI must not implement anything
> that is not written here without first asking and getting it added here.

---

## 0. Document Control

| Field | Value |
|---|---|
| Project Name | [YOUR_PROJECT_NAME] |
| Stack | Laravel 11+, PHP 8.3+, MySQL 8 / PostgreSQL 15, Redis, Livewire or Vue/React (choose one), nwidart/laravel-modules |
| Target Scale | Enterprise multi-vendor marketplace (Daraz / Amazon / Flipkart class) |
| Audience | Gemini CLI acting as Senior Laravel Architect + Dev Team |
| Version | 1.0 |
| Status | Draft — edit before Phase 0 starts |

---

## 1. Project Vision

Build a production-grade, horizontally scalable multi-vendor marketplace where:

- **Admin** owns the platform, approves vendors, takes commission, manages disputes.
- **Vendors** register, get approved, list products, manage stock/orders/payouts independently.
- **Customers** browse, buy from multiple vendors in a single cart/checkout, track orders, request returns.
- **Delivery/Courier** partners fulfill shipments (in-house or 3rd-party API integration).

Non-negotiables:
- Modular architecture (`nwidart/laravel-modules`), one module per business domain.
- No feature listed in this SRS may be silently skipped or stubbed.
- Every module ships with migrations, models, policies, requests, resources, services, tests, and docs — not just controllers.

---

## 2. Actors / Roles

| Role | Description |
|---|---|
| Super Admin | Full platform control |
| Admin Staff | Scoped admin permissions (support, finance, catalog moderation) |
| Vendor Owner | Registers a shop, manages staff, products, orders, payouts |
| Vendor Staff | Scoped permissions under a vendor (e.g., order processor, product manager) |
| Customer | Buys products |
| Delivery Agent | Fulfills/updates shipment status |
| Guest | Unauthenticated browsing |

Use `spatie/laravel-permission` with **team/vendor-scoped roles** (vendor staff permissions must not leak across vendors).

---

## 3. Non-Functional Requirements

| Category | Requirement |
|---|---|
| Performance | Product listing pages must respond < 300ms server-side (cache-first via Redis); search via Meilisearch/Elasticsearch, not raw SQL LIKE |
| Scalability | Stateless app servers; sessions/cache in Redis; horizontal scaling ready |
| Security | OWASP Top 10 covered; rate limiting on auth & payment endpoints; 2FA for vendor/admin financial actions; signed URLs for private media |
| Multi-tenancy | Vendor data logically isolated (vendor_id scoping on every vendor-owned table + global scopes) |
| Auditability | Every financial and status-changing action logged in `activity_logs` |
| Localization | Multi-language (en, bn minimum) and multi-currency from day one |
| Money handling | Store all monetary values as integers (minor units) or `decimal(15,2)`; never float |
| Soft deletes | All business-critical tables (products, orders, vendors, users) use soft deletes |
| Testing | Minimum: Feature tests for every API endpoint, Unit tests for services/actions handling money or stock |
| API | RESTful, versioned (`/api/v1/...`), OpenAPI/Swagger doc generated per module |
| Queues | Emails, SMS, notifications, payout processing, report generation → queued jobs, never synchronous |

---

## 4. Module List & Functional Scope

Each module below becomes a folder under `Modules/` and a Phase in the roadmap (Section 6).

### 4.1 Core
Shared, module-agnostic infrastructure: `countries`, `states`, `cities`, `currencies`, `languages`, `media`, `activity_logs`, `settings`, job/cache tables.

### 4.2 Auth & User
Registration, login, OTP verification (email/SMS), password reset, social login (optional), device/session tracking, profile management for all actor types.

### 4.3 RBAC
Roles & permissions per actor type; vendor-scoped staff permissions; permission matrix (Section 7).

### 4.4 Vendor
Vendor onboarding (KYC docs, trade license, NID/BIN), approval workflow, shop profile, storefront settings, staff management, subscription plans (optional), commission rate per vendor/category, vendor wallet.

### 4.5 Catalog (Product)
Categories (nested), brands, attributes/variants, products, vendor-product mapping (many vendors can sell one catalog product with different price/stock), product media, SEO metadata, product approval workflow (admin moderates new listings).

### 4.6 Inventory
Stock tracking per vendor-product, stock logs, low-stock alerts, warehouse/pickup location per vendor.

### 4.7 Cart
Cart, cart items (grouped by vendor for split checkout), wishlist, recently viewed, compare list.

### 4.8 Checkout
Address selection, shipping method selection per vendor group, coupon application, order preview/summary, tax computation.

### 4.9 Orders
Order splitting by vendor (one customer checkout → N vendor sub-orders), order status machine, order items, order status history, cancellation requests, invoices.

### 4.10 Payments
Payment gateway integrations (COD, SSLCommerz/bKash/Nagad/Stripe — confirm which in this SRS before Phase 10), transaction ledger, refunds.

### 4.11 Finance (Commission & Payout)
Commission calculation per order/vendor, vendor wallet ledger, payout requests, payout batches, settlement reports.

### 4.12 Shipping
Courier integrations, shipping zones/rates, shipment tracking, pickup requests, delivery agent assignment.

### 4.13 Reviews & Ratings
Product reviews (with images), seller ratings, review moderation, helpful-vote system.

### 4.14 Promotions
Coupons (platform-wide and vendor-specific), flash sales, campaigns, banners/sliders.

### 4.15 Search
Full-text/faceted product search (Meilisearch/Elasticsearch), search analytics, autosuggest.

### 4.16 Notifications
Email, SMS, push, in-app notifications; notification preferences per user.

### 4.17 Admin Panel
Dashboards (sales, vendor performance, commission), vendor approval queue, dispute resolution, CMS (pages/blogs), global settings UI.

### 4.18 Support
Ticketing system, live chat (optional), FAQs, complaint/dispute workflow between customer and vendor.

### 4.19 Testing & QA
Test suite completion, coverage report, load testing plan.

### 4.20 Deployment & DevOps
CI/CD pipeline, environment configs, queue/worker setup, backup strategy, monitoring (e.g., Laravel Pulse/Sentry).

---

## 5. High-Level Data Model (Reference)

> Full column-level schema is produced module-by-module during its Phase (per the Master Prompt). This section only fixes the **entity list** so Gemini cannot invent or drop entities.

- **Core:** countries, states, cities, currencies, languages, media, activity_logs, settings
- **Auth/User:** users, otp_codes, devices, sessions
- **RBAC:** roles, permissions, model_has_roles, model_has_permissions, role_has_permissions
- **Vendor:** vendors, vendor_profiles, vendor_addresses, vendor_bank_accounts, vendor_documents, vendor_settings, vendor_staffs, vendor_wallets, vendor_wallet_transactions
- **Catalog:** categories, brands, attributes, attribute_values, products, product_variants, product_variant_values, product_images, product_seo, vendor_products, vendor_product_prices, vendor_product_stocks
- **Inventory:** inventory_logs, warehouses
- **Cart:** carts, cart_items, wishlists, compare_lists, recent_views
- **Orders:** orders, order_items, order_addresses, order_status_histories, cancel_requests, invoices
- **Payments:** payment_methods, payments, transactions, refunds, refund_items
- **Finance:** commissions, vendor_payouts, vendor_settlements
- **Shipping:** couriers, shipping_zones, shipping_rates, shipments, tracking_histories, pickup_requests
- **Reviews:** reviews, review_images, review_votes, seller_reviews
- **Promotions:** coupons, coupon_usages, campaigns, flash_sales, banners
- **Search:** search_logs
- **Notifications:** notifications, email_logs, sms_logs
- **Support:** tickets, ticket_messages, faqs, disputes

---

## 6. Phase Roadmap (Approval-Gated)

Each phase must be completed, reviewed, and explicitly approved (`Proceed Phase X`) before the next starts. See `GEMINI_MASTER_PROMPT.md` for the mandatory per-phase process.

| Phase | Name | Depends On |
|---|---|---|
| 0 | Environment, Architecture, Packages, Folder Structure | — |
| 1 | Core Module | 0 |
| 2 | Authentication | 1 |
| 3 | RBAC | 2 |
| 4 | Vendor Module | 3 |
| 5 | Catalog Module | 4 |
| 6 | Inventory Module | 5 |
| 7 | Cart Module | 6 |
| 8 | Checkout Module | 7 |
| 9 | Orders Module | 8 |
| 10 | Payments Module | 9 |
| 11 | Finance (Commission & Payout) Module | 10 |
| 12 | Shipping Module | 11 |
| 13 | Reviews Module | 5 |
| 14 | Promotions Module | 9 |
| 15 | Search Module | 5 |
| 16 | Notifications Module | 2 |
| 17 | Admin Panel | 4–16 |
| 18 | Support Module | 4, 9 |
| 19 | Testing & QA Hardening | all |
| 20 | Deployment & DevOps | all |

---

## 7. Permission Matrix (to be completed in Phase 3)

| Action | Super Admin | Admin Staff | Vendor Owner | Vendor Staff | Customer |
|---|---|---|---|---|---|
| Approve vendor | ✅ | ⚙️ (if granted) | ❌ | ❌ | ❌ |
| Manage own products | ❌ | ❌ | ✅ | ⚙️ | ❌ |
| Set commission rate | ✅ | ⚙️ | ❌ | ❌ | ❌ |
| Place order | ❌ | ❌ | ❌ | ❌ | ✅ |
| Request payout | ❌ | ❌ | ✅ | ❌ | ❌ |

*(Expand fully during Phase 3 — do not leave this table incomplete past Phase 3.)*

---

## 8. Open Decisions (must be resolved before relevant phase)

- [ ] Frontend stack: Livewire+Blade vs Vue/Inertia vs separate SPA + API-only backend?
- [ ] Payment gateways to integrate first: __________
- [ ] Search engine: Meilisearch vs Elasticsearch?
- [ ] Hosting target: VPS / AWS / DigitalOcean / shared?
- [ ] Single database vs database-per-vendor (recommendation: single DB with `vendor_id` scoping — simpler, sufficient at this scale)?
- [ ] Mobile app needed (API-first requirement) — yes/no?

---

## 9. Definition of Done (applies to every phase)

A phase is NOT done until:
1. Migrations run cleanly with indexes, FKs, cascade rules, soft deletes.
2. Models have fillable/guarded, casts, relationships, scopes.
3. Requests validate all inputs; Policies authorize all actions.
4. Resources shape all API responses; no raw model dumps.
5. Feature tests cover happy path + at least one failure path per endpoint.
6. Module README documents its tables, endpoints, and events.
7. Security review note written (even if "no issues found").
8. Explicit `Proceed Phase X?` approval received from the project owner.