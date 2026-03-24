# EcoShop API — Security Implementation

## Authentication (Laravel Sanctum)

All protected endpoints require:

```bash
Authorization: Bearer {token}
```

### Token Abilities

| Token Type     | Ability      | Issued To            |
| -------------- | ------------ | -------------------- |
| customer-token | ['customer'] | Registered customers |
| admin-token    | ['admin']    | Admins & moderators  |

### Token Lifecycle

* Single session per device name (old token revoked on new login)
* Logout revokes current token
* Logout-all revokes all device tokens

---

## Rate Limiting

| Limiter  | Limit      | Applies To            |
| -------- | ---------- | --------------------- |
| api      | 60 req/min | General API endpoints |
| auth     | 10 req/min | Login & register      |
| checkout | 5 req/min  | Place order           |
| search   | 30 req/min | Search endpoints      |

---

## Brute Force Protection

* After **5 failed login attempts** in a 5-minute window:

  * Account is locked for **15 minutes**
  * Security log entry written
* IP-based attempt tracking (separate counter)
* Successful login clears attempt counters

---

## Role-Based Access Control (RBAC)

### Roles

| Role      | Access                     |
| --------- | -------------------------- |
| admin     | Full platform access       |
| moderator | Read + reviews/orders/chat |
| customer  | Own data only              |

### Permissions (32 total)

```txt
products.view   products.create  products.edit    products.delete
orders.view     orders.manage    orders.cancel    orders.refund
users.view      users.manage     users.ban
categories.view categories.manage
brands.view     brands.manage
coupons.view    coupons.manage
reviews.view    reviews.moderate
cms.view        cms.manage
banners.view    banners.manage
settings.view   settings.manage
affiliate.view  affiliate.manage
reports.view
chat.view       chat.manage
```

---

## Input Security

### SanitizeInput Middleware

* Runs on **every request** globally
* Strips all HTML from plain text fields
* Allows safe HTML only for: `description`, `content`, `body`
* Removes:

  * `onclick`
  * `javascript:`
  * `vbscript:`
  * `data:` attributes
* Removes null bytes (`\0`)
* Skips:

  * `password`
  * `password_confirmation`
  * `current_password`

### Form Request Validation

* Every endpoint has a dedicated FormRequest class
* All inputs have explicit type, length, and format rules
* Custom error messages in English

---

## HTTP Security Headers

Every response includes:

```http
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'; frame-ancestors 'none'
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()
```

Removed fingerprinting headers:

```txt
X-Powered-By
Server
```

---

## CORS

* Only whitelisted origins allowed (`FRONTEND_URL`, `ADMIN_URL`)
* Never use `*` in production
* Credentials supported (for Sanctum SPA cookies)
* Preflight cached for 2 hours

---

## Banned User Protection

* Banned users are blocked at middleware level
* All tokens revoked immediately on ban
* Returns **403 response** with support contact message

---

## Exception Handling

All errors return consistent JSON — never HTML:

```json
{
  "success": false,
  "message": "...",
  "errors": null
}
```

### Status Codes

| Code | Meaning                             |
| ---- | ----------------------------------- |
| 401  | Unauthenticated                     |
| 403  | Forbidden / banned                  |
| 404  | Not found                           |
| 422  | Validation failed                   |
| 429  | Rate limit exceeded                 |
| 500  | Server error (hidden in production) |

---

## Security Logging

All events logged to:

```bash
storage/logs/security.log
```

### Logged Events

* Failed login attempts (email + IP)
* Account lockouts
* Unauthorized access attempts
* IP blocks
* Token revocations
* Banned user access attempts

---

## Admin Security Endpoints

```http
POST   /api/v1/admin/security/unlock-account
POST   /api/v1/admin/security/block-ip
POST   /api/v1/admin/security/revoke-tokens/{user}
```
