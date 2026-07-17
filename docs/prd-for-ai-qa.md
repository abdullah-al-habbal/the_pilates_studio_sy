# Product Requirements Document (PRD) — Pilates Studio Management System

> **Target:** AI QA tools (TestSprite)
> **Version:** 1.0
> **Date:** 2026-07-04
> **Stack:** Laravel 12, PHP 8.4, MySQL, Filament 5, Sanctum, Firebase Cloud Messaging, Tailwind CSS, Alpine.js

---

## 1. Product Overview

The Pilates Studio Management System is a backend platform that powers:

- A **mobile app** (iOS/Android) for customers to browse classes, purchase credit packages, book/reserve sessions, and receive push notifications.
- An **admin web panel** (Filament 5) for full CRUD management of all studio entities (users, classes, instructors, packages, static pages, merchandise, notifications, expenses).
- A **Scheduler web panel** for daily class session management — viewing sessions, marking attendance, and processing walk-ins.
- An **Operations Hub web panel** for packages, store orders, finance (daily balance, expenses), client management, notifications, booking freeze/unfreeze/refund, and expense approvals.
- A **public landing page** with hero, features, class listings, weekly schedule, instructor profiles, pricing packages, testimonials, and download CTAs — fully bilingual (EN/AR).

### 1.1 Architecture Overview

```
Mobile App (iOS/Android)
    ↓ HTTPS JSON
REST API (Sanctum auth)
    ↑
┌──────────────────────────────────────────────────┐
│                 Laravel Backend                   │
│  API Controllers → Services → Repositories → DB │
│  Web Actions → Handlers → DB                     │
│  Filament Admin → Resources → DB                 │
│  Artisan Commands (reminders)                    │
│  Firebase FCM (push notifications)               │
│  Queue (database driver)                         │
└──────────────────────────────────────────────────┘
    ↑
Public Web (Landing Page)
Admin Web (Scheduler + Operations + Filament)
```

**Key technical decisions:**
- Queue driver: `database` (sync for dev)
- Cache store: `database`
- Session driver: `database`
- Auth: Sanctum tokens for API, session-based for web
- Translatable models: Spatie Laravel Translatable (JSON columns)
- Multi-currency: Base USD with exchange rate snapshot on booking
- Soft deletes on most entities (users, bookings, packages, classes, class_sessions, instructors, etc.)

---

## 2. Business Goals

| Goal | Metric | Priority |
|------|--------|----------|
| Seamless mobile booking experience | Booking completion rate ≥ 95% | P0 |
| Reliable push notification delivery | Notification delivered ≥ 99% | P0 |
| Multi-currency financial accuracy | Exchange rate snapshot always recorded | P0 |
| Bilingual (EN/AR) content support | All public content available in both languages | P0 |
| One-active-booking constraint | No user ever holds 2+ active bookings | P0 |
| Session attendance tracking | Every reserved session has attended/missed status | P0 |
| Expense approval workflow | Only MAIN_ADMIN can approve/reject expenses | P0 |
| Class session cancellation policy | Credits only refunded if cancelled >24h before start | P0 |
| Automated session reminders | 24h and periodic reminders sent to enrolled users | P1 |
| Landing page always available | Graceful degradation if any data section fails | P1 |

---

## 3. User Roles and Permissions

### 3.1 Role Hierarchy

| Role | Enum Value | Permissions | Color |
|------|-----------|-------------|-------|
| **Main Admin** | `main_admin` | Full system access — can approve/reject expenses, process refunds, manage all entities, access all admin panels | Danger (red) |
| **Admin** | `admin` | Can manage scheduler, operations (packages, clients, store, finance recording), Filament resources — **cannot** approve/reject expenses or process refunds | Warning (yellow) |
| **Customer** | `customer` | Mobile app access only — can register, login, browse classes, purchase packages, book/reserve/cancel sessions, manage profile/settings | Success (green) |

### 3.2 User Statuses

| Status | Enum Value | Meaning | Middleware Behavior |
|--------|-----------|---------|-------------------|
| Active | `active` | Normal operation | Allow all access |
| Frozen | `frozen` | User temporarily blocked | API returns `FROZEN_USER` error (HTTP 403), web admin blocked |
| Deactivated | `deactivated` | User permanently disabled | Login blocked, API returns `DEACTIVATED_USER` error |

### 3.3 Admin Middleware Guards

| Middleware | Scope | Effect |
|-----------|-------|--------|
| `auth` | Web routes | Redirects unauthenticated to `/admin/login` |
| `auth:sanctum` | API routes | Returns 401 JSON with `UNAUTHORIZED` code |
| `role.admin` | Web admin routes | 403 abort if not MAIN_ADMIN or ADMIN |
| `freeze.user` | API routes | Returns `FROZEN_USER` if user is frozen |
| `ensure.active.package` | API routes | Returns 503 if no active packages exist |

---

## 4. All Public Website Pages

### 4.1 Landing Page (`GET /`)

| Section | Route | Key Content |
|---------|-------|-------------|
| Header | `_header.blade.php` | Logo, nav links (Classes/Schedule/Instructors/Pricing), lang switcher, dark mode toggle, CTA |
| Hero | `_hero.blade.php` | Background image, tagline, headline, subtitle, 2 CTAs, stats (classes/instructors/packages), phone mockup |
| Features | `_features.blade.php` | 4 cards (Instant Booking, Credit Packages, Smart Notifications, Manage on Go) + app store CTAs |
| Classes | `_classes.blade.php` | Category filter buttons, 3-column grid of class cards with image, category badge, spots indicator, instructor, duration |
| Schedule | `_schedule.blade.php` | 7-day tab bar, per-day session list with time, class name, instructor, duration, spots/full indicator, deep-link buttons |
| Instructors | `_instructors.blade.php` | 4-column grid — image, name, title, specialty, bio, social links on hover |
| Packages/Pricing | `_packages.blade.php` | 3 pricing cards with price, credits, validity, features, "most popular" badge, multi-currency badge |
| How It Works | `_how-it-works.blade.php` | 3-step process (Download → Choose Package → Book Class) |
| Testimonials | `_testimonials.blade.php` | 3 cards — star rating, quote, avatar, name, role |
| CTA | `_cta.blade.php` | Gradient banner with "first class free" + app store buttons |
| Footer | `_footer.blade.php` | Logo, social, Explore links, Legal pages, Contact info, copyright, lang selector |

**Error behavior:** If any section fails to load data, `hasError=true` and a generic error banner displays at top. Individual sections gracefully show empty states.

### 4.2 Static Pages (`GET /web/static-pages/{slug}`)

| Slug | Description | Content |
|------|-------------|---------|
| `about-us` | About the studio | Bilingual text |
| `privacy-policy` | Privacy policy | Full legal text (data collection, Firebase, Datadog, user rights, data retention) |
| `terms-of-service` | Terms of service | Bilingual text |
| `cancellation-policy` | Cancellation policy | "Cancel up to 24h before class starts, get credits back" |
| `contact-us` | Contact information | Email contact |

**Behavior:** `StaticPageService::findBySlug()` returns 404 if slug not found or page is inactive.

### 4.3 Filament Admin Pages (all `role.admin`)

| Resource | Route Path | Features |
|----------|-----------|----------|
| Users | `/admin/users` | CRUD, filters, export, status/role management |
| Classes | `/admin/classes` | CRUD, translatable title/about, images, category/instructor assignment |
| Instructors | `/admin/instructors` | CRUD, translatable fields, social links |
| Packages | `/admin/packages` | CRUD, pricing, credits, validity, translatable names |
| Bookings | `/admin/bookings` | View, filter, freeze/unfreeze |
| Static Pages | `/admin/static-pages` | View/edit (no create/delete — seeded from config) |
| App Settings | `/admin/app-settings` | Key-value editor with type validation |
| Class Categories | `/admin/class-categories` | CRUD, colors |
| Recurrence Patterns | `/admin/recurrence-patterns` | CRUD |
| Center Merchandise | `/admin/center-merchandise` | CRUD, images, stock |
| Merchandise Categories | `/admin/merchandise-categories` | CRUD |
| Merchandise Orders | `/admin/merchandise-orders` | View |
| App Notifications | `/admin/app-notifications` | View sent notifications |
| Notification Templates | `/admin/notification-templates` | CRUD, translatable, placeholders |
| Testimonials | `/admin/testimonials` | CRUD, translatable, rating, sort order |

**Filament Widgets (dashboard):**
- StatsOverview (9 stat cards)
- UserStatsOverview, BookingStatsOverview, ClassStatsOverview
- AttendanceTrendChart, TopInstructorsWidget, CategoryPerformanceWidget
- NotificationStatsOverview, InsightsStatsOverview, LanguageStatsOverview
- TopPerformersStatsOverview

**Filament Pages:**
- Reports — daily financial summaries, currency summaries, popular classes, top merchandise

### 4.4 Scheduler Panel (web, `role.admin`)

| Route | Purpose | Features |
|-------|---------|----------|
| `GET /admin/scheduler` | Scheduler main page | Flatpickr date picker, day view |
| `GET /admin/scheduler/sessions?date=` | Daily sessions list | Paginated, filterable by instructor |
| `GET /admin/scheduler/sessions/{id}` | Session details modal | Attendees list, walk-in tab |
| `POST /admin/scheduler/sessions/{id}/attendance/{bsId}` | Mark attendance | Attended/missed |
| `GET /admin/scheduler/instructors` | Instructor dropdown | For filtering |
| `POST /admin/scheduler/sessions/{id}/walkin/existing` | Existing member walk-in | By user ID |
| `POST /admin/scheduler/sessions/{id}/walkin/new` | New member walk-in | Create user on the fly |
| `GET /admin/scheduler/users` | User search | For walk-in assignment |
| `GET /admin/scheduler/walkin/validate` | Field validation | |

### 4.5 Operations Hub (web, `role.admin`)

| Tab | Routes | Features |
|-----|--------|----------|
| **Packages** | `GET /admin/operations/packages`, POST, PUT, DELETE | Full CRUD, assign to user |
| **Store** | `GET /admin/operations/store/items`, POST `/orders`, POST `/walk-in-order` | List merchandise, place orders for existing/walk-in customers |
| **Finance** | `GET /admin/operations/finance/daily`, GET `/categories`, GET `/expenses/breakdown`, POST `/expenses` | Daily balance, expense categories, record expense |
| **Clients** | `GET /admin/operations/clients`, GET `/{userId}/details` | Search/filter clients, view detailed profile with bookings history |
| **Notifications** | `POST /admin/operations/notifications/send` | Send push notification to all users or specific users |
| **Bookings** | `POST /admin/operations/bookings/{id}/freeze`, `/unfreeze`, `/refund` | Freeze/unfreeze booking credits, process refunds |
| **Approvals** | `GET /admin/operations/approvals/pending`, POST `/approve`, POST `/reject` | Only MAIN_ADMIN — approve/reject pending expenses |

---

## 5. Authentication Flows

### 5.1 Registration (`POST /api/v1/auth/register`)

```
Request → RegisterRequest (validates) → AuthService::register()
    → User created (status=active, role=customer, email_unverified)
    → OTP generated (4-digit code, 30min expiry via OTP_EXPIRY_MINUTES=30)
    → OTP stored on user model
    → OTP returned in response (debug/local only, or if RETURN_OTP_IN_RESPONSE=true)
    → SuccessCodeEnum::REGISTER_SUCCESS (201)
```

**Validation rules:**
| Field | Rules |
|-------|-------|
| `fullname` | required, string, max:255 |
| `email` | required, email, unique where `deleted_at IS NULL` |
| `phone_number` | required, string, max:20, unique where `deleted_at IS NULL` |
| `password` | required, string, min:8, confirmed |
| `date_of_birth` | nullable, date, before:today |

### 5.2 Login (`POST /api/v1/auth/login`)

```
Request → LoginRequest (validates) → AuthService::attemptLogin()
    → Find user by email
    → Verify password
    → Check user isActive() — if not, return FROZEN_USER or DEACTIVATED_USER
    → Check email_verified_at:
        → If verified: create Sanctum token, return {token, user}
        → If unverified: send OTP, return EMAIL_NOT_VERIFIED error (includes user info + OTP in dev)
```

### 5.3 Email Verification (OTP)

**Verify OTP:** `POST /api/v1/auth/email/verify` → `VerifyOtpRequest`

| Step | Guard | Behavior |
|------|-------|----------|
| Pre-verify | `isEmailVerified()` | If already verified, return `EMAIL_ALREADY_VERIFIED` |
| Validate OTP | `hasValidOtp($otp)` | Compare `user->otp_code` |
| Check expiry | `isOtpExpired()` | Compare `otp_expires_at < now()` |
| Verify | `verifyOtp()` | Set `email_verified_at=now()`, clear OTP fields |
| Token | — | Create Sanctum token with device name "mobile" |
| Response | — | `EMAIL_VERIFIED` code, 201, returns `{token, user}` |

**Resend OTP:** `POST /api/v1/auth/email/resend` → generates new OTP → returns `VERIFICATION_EMAIL_SENT`

### 5.4 Logout (`POST /api/v1/auth/logout`)

- Protected by `auth:sanctum`
- Deletes current Sanctum token → HTTP 204

### 5.5 Current User (`GET /api/v1/auth/me`)

- Protected by `auth:sanctum`
- Returns `RichUserResource` including `activeCreditBooking.package`

### 5.6 FCM Token Management

| Endpoint | Action | Validation |
|----------|--------|-----------|
| `POST /api/v1/fcm-token` | Store/update device FCM token | `fcm_token: required, string, max:500` |
| `DELETE /api/v1/fcm-token` | Remove device FCM token | — |

### 5.7 Locale Switching

- **Web:** `GET /locale/{code}` → sets session locale, redirects back
- **API:** `POST /api/v1/languages/set-locale` → body `{code: "en|ar"}` → updates user settings + session
- **Auto-detection:** `SetLocaleMiddleware` checks session → `X-Locale` header → DB default language → fallback locale

---

## 6. Admin, Center, and Trainer Panels

### 6.1 Admin Panel (Filament 5)

- URL: `/admin`
- Auth: Session-based, `auth` + `role.admin` middleware
- Navigation: Users, Classes, Instructors, Packages, Bookings, Merchandise, Configuration, Testimonials
- Global search, dark mode, translatable resources
- 11+ dashboard widgets, Reports page

### 6.2 Scheduler Panel

- URL: `/admin/scheduler`
- Layout: Tailwind + Flatpickr + dark mode
- Auth: `web`, `auth`, `freeze.user`, `role.admin`
- Flows: Select date → view sessions → click session → modal with attendees + walk-in → mark attendance
- State: Vanilla JS pub/sub, centralized API client, toaster stack

### 6.3 Operations Hub

- URL: `/admin/operations`
- Layout: Tailwind + glassmorphism + SweetAlert2
- Auth: `web`, `auth`, `freeze.user`, `role.admin`
- Tabs: Packages, Store, Finance, Clients, Notifications, Bookings, Approvals
- All UIs use template-based modals, fetched via AJAX

---

## 7. Core Business Workflows

### 7.1 Booking Lifecycle

```
1. Purchase package → Booking created (total_credits = package.total_credits)
   → Status ACTIVE, expires_at = now + validity_days
   → Exchange rate snapshot recorded
   → One-active-booking enforced (DB generated column + app check)

2. Reserve session → BookingSession created (RESERVED)
   → Guards: active booking exists, credits >0, not expired, session available, not full
   → Booking.deductCredit() → remaining_credits--
   → If remaining_credits == 0 → status EXHAUSTED

3. Cancel session (user) → BookingSession status CANCELLED
   → Guards: session reserved, NOT within 24h window, NOT past
   → Booking.refundCredit() → remaining_credits++
   → If was EXHAUSTED → back to ACTIVE

4. Attendance (admin) → ATTENDED or MISSED
   → ATTENDED: session start time in past
   → MISSED: session end time in past
   → Missed = credit NOT refunded

5. Expiry → status EXPIRED, remaining credits forfeited
```

### 7.2 Package Purchase Flow

- **Customer:** `POST /api/v1/bookings` with `package_id` → booking created
- **Admin back-office:** `POST /admin/operations/packages/{id}/assign` with `user_id` → booking created

### 7.3 Walk-In Flow

- **Existing member:** Search user → POST walkin/existing → validates available spots → creates BookingSession
- **New member:** Form (fullname, phone required; email/password optional, default password "pilates") → creates user → creates BookingSession

### 7.4 Expense Workflow

1. Admin records expense → status PENDING
2. MAIN_ADMIN reviews → approve (status APPROVED) or reject (with rejection_reason)
3. Non-main-admin → 403

### 7.5 Freeze/Unfreeze

- **Freeze:** Booking status = FROZEN, existing sessions remain, no new reservations
- **Unfreeze:** Creates NEW booking with remaining credits/validity (source_type = freeze_resume). Original stays FROZEN.

### 7.6 Refund (MAIN_ADMIN only)

- Creates Refund record (morph refundable → booking)
- Optional amount (handler calculates if absent)
- Min refund: 100 (configurable), partial refunds allowed (configurable)

### 7.7 Push Notifications

- **Manual:** Admin sends title+body → all/specific users → queries users with fcm_token → dispatches queued notification → returns stats
- **Automated:** `sessions:send-reminders` (every 5min), `sessions:remind-24h` (08:00 daily) → `session_reminder` template with placeholders
- **FCM pipeline:** Queued (3 retries) → validate config → build message → send → log → detect invalid tokens → clean up

---

## 8. CRUD Operations

### 8.1 Complete CRUD Matrix

| Entity | Create | Read | Update | Delete | Soft Del | Notes |
|--------|--------|------|--------|--------|----------|-------|
| Users | Register + admin assign | API+Filament | Profile+Filament | Self+Filament | Yes | |
| Packages | Filament+Ops | API+Web+Filament | Ops+Filament | Ops+Filament | Yes | |
| Classes | Filament | API+Web+Filament | Filament | Filament | Yes | |
| Class Sessions | Auto-generated | API+Scheduler | — | — | Yes | Not manually CRUDed |
| Class Categories | Filament | Filament | Filament | Filament | Yes | |
| Instructors | Filament | API+Web+Filament | Filament | Filament | Yes | |
| Bookings | API+Ops assign | API+Filament+Client | Freeze/unfreeze | — | Yes | System-created |
| Booking Sessions | API reserve+walk-in | API+Scheduler | Cancel | — | No | System-created |
| Static Pages | Config-seeded | Web+Filament | Filament (title/content) | — | — | No create/delete |
| App Settings | Seeder | API+Filament | Filament | — | — | Key-value |
| Merchandise | Filament | Store tab | Filament | Filament | Yes | |
| Merchandise Categories | Filament | Store tab | Filament | Filament | Yes | |
| Merchandise Orders | Ops tab | Filament | — | — | Yes | |
| Expenses | Ops tab | Finance tab | — | — | No | Immutable |
| Push Notifications | Ops tab | — | — | — | No | One-shot |
| Notification Templates | Filament | Filament | Filament | Filament | No | |
| Testimonials | Filament | Web+Filament | Filament | Filament | No | |
| Recurrence Patterns | Filament | Filament | Filament | Filament | No | |
| App Notifications | API+System | API | Mark as read | — | No | System-generated |
| User Settings | Auto on register | API | API | On delete | No | |

### 8.2 API Endpoints Summary

| Group | Endpoints | Auth |
|-------|-----------|------|
| **Auth** | `POST register`, `POST login`, `POST logout`, `GET me`, `POST fcm-token`, `DELETE fcm-token` | Mixed |
| **Email Verification** | `POST verify`, `POST resend` | Public |
| **Public** | `GET classes`, `GET classes/{id}`, `GET class-sessions`, `GET class-sessions/{id}`, `GET instructors/{id}`, `GET pages`, `GET pages/{slug}`, `GET app-settings`, `GET app-settings/{key}`, `GET languages` | Public |
| **Protected** | `GET/PATCH/DELETE profiles`, `GET/POST bookings`, `GET/POST/GET{id}/POST{id}/cancel booking-sessions`, `GET notifications + read`, `GET packages/{id}`, `GET/PATCH user-settings`, `POST set-locale`, `POST app-version` | Sanctum |
| **Admin** | `GET /v1/admin/health` | Throttled |

---

## 9. Forms and Validation Rules

### 9.1 API Validation

| Form Request | Rules |
|-------------|-------|
| `RegisterRequest` | `fullname: required\|string\|max:255`, `email: required\|email\|unique:users,email whereNull deleted_at`, `phone_number: required\|string\|max:20\|unique:users,phone_number whereNull deleted_at`, `password: required\|string\|min:8\|confirmed`, `date_of_birth: nullable\|date\|before:today` |
| `LoginRequest` | `email: required\|email`, `password: required\|string`, `device_name: nullable\|string\|max:255` |
| `VerifyOtpRequest` | `email: required\|email\|exists:users,email`, `otp: required\|string\|size:4` |
| `ResendOtpRequest` | `email: required\|email\|exists:users,email` |
| `StoreFcmTokenRequest` | `fcm_token: required\|string\|max:500` |
| `UpdateProfileRequest` | `fullname: sometimes\|string\|max:255`, `phone_number: sometimes\|string\|max:20`, `date_of_birth: sometimes\|nullable\|date\|before:today`, `email: sometimes\|email\|unique:users,email ignore current user` |
| `CreateBookingRequest` | `package_id: required\|exists:packages,id` |
| `ReserveSessionRequest` | `class_session_id: required\|exists:class_sessions,id` |
| `BulkMarkAsReadRequest` | `ids: required\|array\|min:1\|max:100`, `ids.*: integer\|exists:app_notifications,id` |
| `QueryClassesRequest` | `date: nullable\|date_format:Y-m-d`, `start_after: nullable\|date_format:H:i`, `start_before: nullable\|date_format:H:i`, `category_id: nullable\|exists:class_categories,id`, `instructor_id: nullable\|exists:instructors,id`, `per_page: integer\|min:1\|max:100` |
| `QueryClassSessionsRequest` | `date: nullable\|date_format:Y-m-d`, `date_after/before: nullable\|date_format:Y-m-d`, `start_after: nullable\|date_format:H:i`, `class_id: nullable\|exists:classes,id`, `per_page: integer\|min:1\|max:100` |
| `ListUserSessionsRequest` | `type: nullable\|in:upcoming,past,both`, `per_page: nullable\|integer\|min:1\|max:100` |
| `UpdateUserSettingRequest` | `preferred_language_id: nullable\|exists:languages,id`, `allow_notifications: nullable\|boolean`, `fcm_token: nullable\|string\|max:255` |

### 9.2 Admin Web Validation

| Form | Rules |
|------|-------|
| `CreatePackageRequest` | `name: required\|string\|max:255`, `total_credits: required\|integer\|min:1`, `validity_days: nullable\|integer\|min:0`, `currency_id: nullable\|integer\|exists:currencies,id`, `amount: required\|integer\|min:0` |
| `UpdatePackageRequest` | Same as CreatePackageRequest |
| `AssignPackageRequest` | `user_id: required\|exists:users,id`, `currency_id: nullable\|integer\|exists:currencies,id`, `paid_amount: nullable\|integer\|min:1` |
| `ProcessExistingWalkInRequest` | `user_ids: required\|array\|min:1` + custom closure for available spots, `user_ids.*: integer\|exists:users,id` |
| `ProcessNewWalkInRequest` | `fullname: required\|string\|max:255`, `phone_number: required\|string\|max:20\|unique:users,phone_number whereNull deleted_at`, `email: nullable\|email\|max:255\|unique:users,email whereNull deleted_at`, `password: nullable\|string\|min:6` |
| `RecordExpenseRequest` | `category_name: required\|string\|max:255`, `currency_id: required\|integer\|exists:currencies,id`, `amount: required\|integer\|min:1`, `notes: nullable\|string\|max:2000`, `date: nullable\|date` |
| `RejectExpenseRequest` | `rejection_reason: required\|string\|max:2000` (plus custom check status=PENDING) |
| `SendPushNotificationRequest` | `title: required\|string\|max:255`, `body: required\|string\|max:1000`, `target: required\|in:all,specific`, `user_ids: required_if:target,specific\|array\|min:1`, `user_ids.*: integer\|exists:users,id` |
| `PlaceOrderRequest` | `customer_id: required\|exists:users,id`, `merchandise_id: required\|exists:center_merchandises,id`, `quantity: required\|integer\|min:1`, `currency_id: required\|exists:currencies,id` |
| `UpdateAttendanceRequest` | `status: required\|in:attended,missed` |
| `GetClientsRequest` | `search: nullable\|string\|max:255`, `page: nullable\|integer\|min:1`, `per_page: nullable\|integer\|min:1\|max:100`, `filter: nullable\|in:best_user,most_active_booking,best_seller,most_attended`, `only_clients: nullable\|boolean`, `with_valid_fcm: nullable\|boolean` |
| `GetDailySessionsRequest` | `date: nullable\|date_format:Y-m-d`, `page: nullable\|integer\|min:1`, `per_page: nullable\|integer\|min:1\|max:50`, `instructor_id: nullable\|integer\|exists:instructors,id` |

### 9.3 DB Unique Constraints

- `unique(users.phone_number)` where active (soft-delete aware)
- `unique(users.email)` where active (soft-delete aware)
- `unique(bookings.active_booking_per_user)` — stored generated column
- `unique(bookings_sessions.booking_id + class_session_id)`
- `unique(prices.priceable_type + priceable_id + currency_id)`
- `unique(mobile_app_versions.app_name + platform)`

### 9.4 Error Response Format

**Success:**
```json
{"success":true,"code":"SUCCESS","message":"...","data":{...},"timestamp":"...","status_code":200}
```

**Error:**
```json
{"success":false,"code":"ERROR_CODE","message":"...","timestamp":"...","status_code":422}
```

**Validation Error:**
```json
{"success":false,"code":"VALIDATION_FAILED","message":"Validation failed","errors":{...},"timestamp":"...","status_code":422}
```

---

## 10. Search, Filtering, Pagination

### 10.1 Filtering

| Endpoint | Filters |
|----------|---------|
| `GET /api/v1/classes` | `date` (Y-m-d), `start_after`, `start_before`, `category_id`, `instructor_id`, `per_page` (1-100) |
| `GET /api/v1/class-sessions` | `date`, `date_after`, `date_before`, `start_after`, `class_id`, `per_page` (1-100) |
| `GET /api/v1/booking-sessions` | `type` (upcoming/past/both), `per_page` (1-100) |
| `GET /api/v1/notifications` | `unread` (boolean) |
| Scheduler sessions | `date`, `instructor_id`, `per_page` (1-50) |
| Clients list | `search`, `filter` (best_user/most_active_booking/best_seller/most_attended), `only_clients`, `with_valid_fcm`, `per_page` (1-100) |
| Finance daily | `date`, `currencies[]`, `convertToBase` |

### 10.2 Pagination Meta

```json
{"current_page":1,"last_page":5,"per_page":20,"total":93,"from":1,"to":20}
```

For `type=both`:
```json
{"upcoming":{"items":[...],"meta":{...}},"past":{"items":[...],"meta":{...}}}
```

### 10.3 Eager Loading

`GET /api/v1/instructors/{id}?include=classes,classes.category,classes.primaryImage`

---

## 11. Localization (Arabic/English)

| Language | Code | Direction | Default |
|----------|------|-----------|---------|
| English | `en` | LTR | Yes |
| Arabic | `ar` | RTL | No |

### 11.1 Translatable Model Columns

| Model | Translatable Columns |
|-------|---------------------|
| `Classes` | `title`, `about` |
| `Instructor` | `name`, `title`, `specialty`, `bio` |
| `Package` | `name` |
| `ClassCategory` | `name` |
| `AppNotification` | `title`, `message` |
| `StaticPage` | `title`, `content` |
| `NotificationTemplate` | `title`, `body` |
| `Testimonial` | `name`, `role`, `quote` |
| `CenterMerchandise` | `name`, `description` |
| `CenterMerchandiseCategory` | `name` |

### 11.2 Locale Resolution

Session → `X-Locale` header → DB default language → `app.fallback_locale` (EN)

### 11.3 Lang Files

| File | Keys | Notes |
|------|------|-------|
| `lang/en/landing.php` | 86 | Nav, hero, features, classes, schedule, pricing, steps, testimonials, footer, errors |
| `lang/ar/landing.php` | 86 | Complete Arabic translation |
| `lang/en/notifications.php` | 2 | Session reminder EN |
| `lang/ar/notifications.php` | 2 | ⚠ Contains English text — needs Arabic translation |

---

## 12. Notifications

### 12.1 Types

| Type | Trigger | Channel | Template |
|------|---------|---------|----------|
| Session Reminder (24h) | Cron 08:00 daily | FCM + Database | `session_reminder` |
| Session Reminder (periodic) | Cron every 5min | FCM + Database | `session_reminder` |
| Manual Push | Admin Operations Hub | FCM | `ManualPushNotification` |

### 12.2 Placeholders

`session_reminder` template: `:class`, `:instructor`, `:time`, `:date`

### 12.3 FCM Pipeline

```
Notification → toFcm() → FcmChannel (queued, 3 retries, 60/300/900s)
  → FcmTokenGetter → FcmConfigValidator → FcmMessageBuilder (high priority, sound, badge)
  → FcmSender → FcmLogSaver → FcmInvalidTokenDetector → FcmTokenDeleter
```

### 12.4 Storage

- `app_notifications` — per-user (user_id, title, message, data, read_at)
- `push_notification_logs` — send log (notifiable, notification_class, channel, sent_at)

### 12.5 In-App Notification API

| Endpoint | Purpose |
|----------|---------|
| `GET /api/v1/notifications` | List (optional `unread` filter) |
| `GET /api/v1/notifications/{id}` | Single detail |
| `PATCH /api/v1/notifications/{id}/read` | Mark read |
| `PATCH /api/v1/notifications/bulk/read` | Bulk mark read (ids array, max 100) |

---

## 13. Error Scenarios

### 13.1 Error Codes

| Code | HTTP | Scenario |
|------|------|----------|
| `UNAUTHORIZED` | 401 | Missing/expired/invalid Sanctum token |
| `FORBIDDEN` | 403 | User role lacks permission |
| `ENDPOINT_NOT_FOUND` | 404 | Route not found |
| `{MODEL}_NOT_FOUND` | 404 | Model lookup failed |
| `VALIDATION_FAILED` | 422 | Form validation errors |
| `INVALID_CREDENTIALS` | 401 | Wrong email/password |
| `EMAIL_NOT_VERIFIED` | 403 | Login before email verification |
| `EMAIL_ALREADY_VERIFIED` | 422 | Re-verify verified email |
| `INVALID_VERIFICATION_CODE` | 422 | OTP mismatch |
| `FROZEN_USER` | 403 | Frozen user actions |
| `DEACTIVATED_USER` | 403 | Deactivated user login |
| `NO_ACTIVE_BOOKING` | 422 | Reserve without active booking |
| `INSUFFICIENT_CREDITS` | 422 | No remaining credits |
| `BOOKING_EXPIRED` | 422 | Booking validity passed |
| `SESSION_FULL` | 422 | No available spots |
| `ALREADY_RESERVED` | 422 | Double booking attempt |
| `CANCELLATION_WINDOW_PASSED` | 422 | Cancel within 24h of start |
| `SESSION_EXPIRED` | 422 | Session already past |
| `INVALID_BOOKING_STATUS` | 422 | Wrong status for operation |
| `OUT_OF_STOCK` | 422 | Merchandise insufficient |
| `APP_VERSION_REQUIRED` | 428 | Missing app version headers |
| `APP_VERSION_OUTDATED` | 426 | App below minimum version |
| `INTERNAL_SERVER_ERROR` | 500 | Unhandled (generic in prod) |
| `TOO_MANY_REQUESTS` | 429 | Rate limited |

### 13.2 Edge Cases

| Scenario | Expected Behavior |
|----------|------------------|
| Duplicate email/phone on register | `VALIDATION_FAILED` |
| User with 0 credits tries to reserve | `INSUFFICIENT_CREDITS` |
| User with no booking tries to reserve | `NO_ACTIVE_BOOKING` |
| Buy 2nd active package | Blocked by `is_available_for_purchase` |
| Cancel within 24h window | `CANCELLATION_WINDOW_PASSED` |
| Mark attendance before session starts | Prevented by `can_mark_attended` guard |
| Walk-in to full class | Rejected by custom validation closure |
| Expense approval by non-main-admin | 403 forbidden |
| All packages deactivated | 503 on all API routes |
| Soft-deleted user tries login | `INVALID_CREDENTIALS` |
| Landing page DB error on any section | Section fails silently, `hasError=true`, page renders |
| Firebase credentials missing | FCM fails silently (logged) |
| Invalid FCM token | Detected + cleaned by pipeline |
| Concurrent last-spot reservation | Unique constraint prevents double-book; credit race condition possible |
| Static page slug not found | 404 |
| Arabic locale | RTL layout, translated content |
| OTP exposed in production | Only if `RETURN_OTP_IN_RESPONSE=true` or `APP_ENV=local` |

---

## 14. Security Requirements

- **Auth:** Sanctum tokens (no expiry), session-based for web
- **Role-based authorization:** Middleware + explicit `isMainAdmin()` gates
- **CSRF:** Enabled on web routes
- **Rate limiting:** `throttle:api` on all API, `throttle:60,1` on health
- **App version gating:** Blocks outdated mobile apps
- **Frozen user blocking:** Middleware on all API routes
- **Password hashing:** bcrypt
- **OTP:** 4-digit, 30min expiry, exposed only in dev
- **Soft deletes:** Prevent permanent data loss
- **Exchange rate snapshots:** Prevent financial disputes
- **Known gaps:** No rate limiting on auth endpoints, no 2FA beyond email verification, no audit log beyond Filament

---

## 15. Performance Expectations

| Endpoint | P95 Target |
|----------|-----------|
| Public reads (classes, sessions) | < 200ms |
| Auth (login, register) | < 500ms |
| Booking/reserve operations | < 500ms |
| Notification listing | < 300ms |
| Admin scheduler daily view | < 500ms |
| Admin operations (finance, clients) | < 1s |

**Cache:** Database driver. Currency exchange rate TTL: 600s (prod), 3600s (dev).
**Queue:** Database driver. Notifications queued with 3 retries, exponential backoff (60/300/900s).

---

## 16. Known Business Constraints

| Constraint | Rationale |
|-----------|-----------|
| One active booking per user | Prevents credit fragmentation |
| 24-hour cancellation window | Standard studio policy |
| Exchange rate snapshot on booking | Financial audit trail |
| Credits not refunded on missed sessions | Standard studio policy |
| Soft deletes on all major entities | Data recovery |
| Only MAIN_ADMIN can approve/reject expenses | Separation of duties |
| Min refund amount: 100 | Prevents trivial refunds |
| Partial refunds allowed (configurable) | Business flexibility |
| Walk-in users created without email verification | Front desk speed |
| Bookings immutable after creation | Financial integrity |
| Class sessions auto-generated from recurrence | Consistency |

---

## 17. Features That Must Never Break (P0)

- Login/Auth — all users locked out if broken
- Email verification (OTP) — new users cannot complete registration
- Booking (purchase a package) — revenue stopped
- Session reservation — core product value
- Session cancellation (within policy) — user trust
- Attendance marking — studio cannot track utilization
- One-active-booking constraint — credit accounting corrupted
- Exchange rate snapshot — financial records inconsistent
- Push notification delivery — users miss reminders
- Landing page — public website down
- Frozen user blocking — security bypass
- Admin scheduler walk-in — front desk cannot process customers
- Finance recording — expense tracking corrupted

---

## 18. Test Priorities

### P0 — Critical

**Auth:**
- Register with valid data
- Register with duplicate email → `VALIDATION_FAILED`
- Register with duplicate phone → `VALIDATION_FAILED`
- Login with valid email/password → token + user
- Login with wrong password → `INVALID_CREDENTIALS`
- Login with unverified email → OTP sent, `EMAIL_NOT_VERIFIED`
- Verify OTP correct → email verified, token returned
- Verify OTP expired → error
- Verify OTP wrong → `INVALID_VERIFICATION_CODE`
- Logout → token deleted, 204
- GET /me → returns correct authenticated user

**Booking:**
- Purchase package → booking with correct credits, 201
- Cannot purchase 2nd active booking
- Reserve session → credit deducted, BookingSession created
- Reserve when booking exhausted → `INSUFFICIENT_CREDITS`
- Reserve when session full → `SESSION_FULL`
- Reserve duplicate session → `ALREADY_RESERVED`
- Cancel session (outside 24h window) → credit refunded, status cancelled
- Cancel session (within 24h) → `CANCELLATION_WINDOW_PASSED`

**Attendance (Admin Scheduler):**
- Mark session as attended
- Mark session as missed
- Verify credit NOT refunded on missed

**Walk-In (Admin Scheduler):**
- Add existing user to session
- Create new user + add to session
- Walk-in to full session → rejected

**Frozen User:**
- Frozen user login blocked
- Frozen user API calls blocked → `FROZEN_USER`
- Admin freezes booking → status FROZEN
- Unfreeze booking → new ACTIVE booking created

**One Active Booking:**
- User with active booking cannot purchase another package
- DB unique constraint prevents duplicate active bookings

**Landing Page:**
- Loads without error (200)
- Shows content when all data available
- Shows error notice when data fetch fails for any section
- Returns 503 when no app settings configured

### P1 — High

**Role Gates:**
- Admin cannot approve expenses → 403
- Admin cannot reject expenses → 403
- Admin cannot process refunds → 403
- Main admin can approve/reject/refund

**Expense Workflow:**
- Record expense → status PENDING
- Main admin approves → status APPROVED
- Main admin rejects with reason → status REJECTED
- Reject without reason → validation error
- Non-pending expense cannot be approved/rejected

**Package CRUD:**
- Create, read, update, delete package (soft delete)
- Assign package to user → booking created
- Cannot create package with negative credits

**Push Notifications:**
- Send to all users with FCM tokens
- Send to specific users
- Sending to user without FCM token → skipped
- Notification logged in `push_notification_logs`

**User Settings:**
- Update preferred language
- Toggle allow_notifications
- Store FCM token
- Delete FCM token

**Profile:**
- Update fullname, phone, email
- Update email → uniqueness check
- Delete account → soft delete

**Notification List:**
- List with pagination
- Mark single notification as read
- Bulk mark notifications as read

### P2 — Medium

**Finance:**
- Daily balance with single currency
- Daily balance with multiple currencies
- Daily balance with base currency conversion
- Expense breakdown by category

**Clients:**
- Search by name, phone, email
- Filter by type (best_user, most_active_booking, etc.)
- Pagination works
- Client detail view with booking/session history

**Store:**
- List merchandise items
- Place order for existing customer
- Place order for walk-in (creates user)
- Order out-of-stock item → `OUT_OF_STOCK`

**Static Pages:**
- Public view by slug (200)
- Unknown slug → 404
- Admin edit page content → saves correctly

**App Settings API:**
- List all settings (200)
- Get setting by key (200)
- Unknown key → 404

**App Version Gate:**
- Valid version → passes
- Outdated version → `APP_VERSION_OUTDATED` (426)
- Missing headers → `APP_VERSION_REQUIRED` (428)

**Localization:**
- Switch locale via X-Locale header
- Switch locale via API
- RTL CSS applied for Arabic
- Translatable models return correct language
- Landing page renders in Arabic

### P3 — Low

- Testimonials CRUD via Filament
- Merchandise CRUD via Filament
- Recurrence Patterns CRUD via Filament
- Class Categories CRUD via Filament
- Notification Templates CRUD via Filament
- Filament dashboard widgets render without error
- Filament Reports page loads
- Debug FCM route (dev only)
- Artisan commands run without error

### Security Tests

| Test | Priority |
|------|----------|
| Guest redirected to /admin/login | P1 |
| API without token → 401 | P1 |
| Customer accessing admin routes → 403 | P0 |
| CSRF protection on web POST | P1 |
| OTP not exposed in production | P1 |
| XSS in translatable content (via `{!! !!}`) | P2 |
| Rate limiting on health endpoint | P2 |

### Error Handling Tests

| Test | Priority |
|------|----------|
| Validation errors return consistent JSON format | P1 |
| Unhandled exceptions → INTERNAL_SERVER_ERROR (generic in prod) | P1 |
| Model not found → `{MODEL}_NOT_FOUND` | P2 |
| 404 for unknown route → `ENDPOINT_NOT_FOUND` | P2 |
| Landing page partial failure → graceful display | P1 |

### Performance Tests

| Test | Priority |
|------|----------|
| Classes list with filters < 200ms (P95) | P2 |
| Reserve session < 500ms (P95) | P2 |
| Admin daily sessions < 500ms (P95) | P2 |
| Concurrent reservation of last spot (no double-book) | P1 |

---

## Appendix A: Database Schema

| Table | Key Columns | Key Indexes |
|-------|-------------|-------------|
| `users` | id, fullname, phone_number, email, password, status, role, otp_code, otp_expires_at, frozen_at, deleted_at | PK, unique(email+active), unique(phone+active) |
| `packages` | id, name(json), total_credits, is_active, type, validity_days, features, deleted_at | PK |
| `classes` | id, instructor_id, class_category_id, recurrence_pattern_id, title(json), about(json), start_time, end_time, start_date, end_date, total_spots, status, deleted_at | PK, FK instructor/category/recurrence |
| `class_sessions` | id, class_id, date, start_time, end_time, total_spots, status, deleted_at | PK, FK class, unique(class_id+date+start_time) |
| `bookings` | id, user_id, package_id, total_credits, remaining_credits, status, expires_at, paid_amount, currency_id, exchange_rate_snapshot, source_type, parent_booking_id, frozen_at, deleted_at | PK, FK user/package/currency, unique active per user |
| `booking_sessions` | id, booking_id, class_session_id, status, cancelled_at, attendance_status, attended_at | PK, FK booking/class_session, unique(booking_id+class_session_id) |
| `instructors` | id, name/json, title/json, specialty/json, bio/json, social_links, image, deleted_at | PK |
| `club_expenses` | id, category_id, currency_id, amount, notes, status, approved_by, rejected_by, rejection_reason, recorded_by, expense_date | PK, FK category/currency |
| `club_expense_categories` | id, name | PK, unique name |
| `currencies` | id, code, name(json), symbol, decimal_places, exchange_rate, is_active | PK, unique code |
| `languages` | id, code, name, direction, is_active, is_default | PK, unique code |
| `prices` | id, priceable_type, priceable_id, currency_id, amount | PK, unique(priceable_type+priceable_id+currency_id) |
| `app_settings` | id, key, value, type, description | PK, unique key |
| `app_notifications` | id, user_id, image, title(json), message(json), data(json), type, read_at | PK, FK user cascade |
| `user_settings` | id, user_id, preferred_language_id, allow_notifications, fcm_token | PK, unique FK user |
| `static_pages` | id, slug, title(json), image, content(json), is_active, sort_order | PK, unique slug |
| `center_merchandises` | id, name(json), description(json), stock_quantity, category_id, deleted_at | PK, FK category |
| `merchandise_orders` | id, merchandise_id, quantity, customer_id, created_by, ordered_at, currency_id, paid_amount, deleted_at | PK, FK merchandise/customer |
| `refunds` | id, refundable_type, refundable_id, user_id, currency_id, amount, reason, refunded_by, refunded_at | PK, morph index |
| `personal_access_tokens` | id, tokenable_type, tokenable_id, name, token, abilities | PK, unique token |
| `testimonials` | id, name(json), role(json), quote(json), avatar, rating, is_active, sort_order | PK |

## Appendix B: Environment Config for Testing

| Variable | Test Value | Purpose |
|----------|-----------|---------|
| `RETURN_OTP_IN_RESPONSE` | `true` | Expose OTP in API response |
| `APP_ENV` | `testing` | Control error message exposure |
| `CURRENCY_BASE` | `USD` | Base currency |
| `FINANCIAL_MIN_REFUND_AMOUNT` | `100` | Min refund threshold |
| `FINANCIAL_ALLOW_PARTIAL_REFUNDS` | `true` | Allow partial refunds |
| `APP_LOCALE` | `en` | Default locale |
| `APP_FALLBACK_LOCALE` | `en` | Fallback locale |
