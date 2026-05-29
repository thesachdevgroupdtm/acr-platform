# Phase 4.2 — Manual Verification Checklist

Run after Claude Code completes report. Each item ~30 sec.

## Setup

1. Backend running: `cd backend && php artisan serve`
2. Browser: <http://127.0.0.1:8000/admin>
3. Login: `admin@acr-mechanics.in` / `change-me-on-first-login`

## Dashboard

- [ ] OperationsStats widget renders at top of dashboard
- [ ] 4 stats visible: **Pending Orders**, **Today's Bookings**,
      **This Week's Revenue**, **Active Customers**
- [ ] No console errors (F12 → Console tab)
- [ ] Sidebar shows 5 entries: Orders, Users, Coupons,
      Service Categories, Services

## OrderResource (`/admin/orders`)

- [ ] List page renders, default sort = created_at desc
- [ ] Status badges show correct color (pending=warning,
      confirmed=info, in_service=info, completed=success,
      cancelled=danger)
- [ ] Filters present: Status (multi-select), Service Center,
      Date Range (from/to), Today, This Week
- [ ] Click any pending order → "Confirm" action visible
- [ ] Click any completed order → Confirm/Cancel/MarkCompleted
      NOT visible
- [ ] Edit form: Customer section read-only (name/phone/email),
      Snapshots formatted as readable list (NOT raw JSON)
- [ ] Cancel action shows reason textarea (required, min 10
      chars)
- [ ] Mark Completed only visible on confirmed orders

## UserResource (`/admin/users`)

- [ ] List page renders with admin user visible
- [ ] Edit your own admin user → "Toggle Admin" action
      DISABLED (self-protection — tooltip says "Cannot toggle
      your own admin status.")
- [ ] Edit any user → form has NO password field
- [ ] Edit a user with `is_verified_phone = true` → phone
      field is disabled with helper text
- [ ] Edit a user with `is_verified_phone = false` → phone
      field is editable
- [ ] Filters: Admin, Phone Verified, Has Orders all selectable

## CouponResource (`/admin/coupons`)

- [ ] List page renders, FIRST10 / ACCOOL20 / SAVER15 visible
      (existing seeded coupons)
- [ ] Edit FIRST10 → terms field is RichEditor (toolbar visible:
      bold, italic, ordered/bullet lists, link, blockquote,
      H2, H3 — NO image/codeBlock/attachFiles)
- [ ] Create new coupon → type code as `mixed10` → save → list
      shows it as **MIXED10** (uppercase mutator works)
- [ ] Filters: Active, Discount Type, Currently valid, Expired
- [ ] Discount type=percent shows "Cap for percent discount"
      input; switching to flat hides it

## ServiceCategoryResource (`/admin/service-categories`)

- [ ] List shows ~12 seeded categories sorted by position
- [ ] Drag-drop reorder works (handle on left of each row)
      and the position column updates server-side
- [ ] Create page → typing name auto-fills slug
- [ ] Try delete a category that has services → blocked with
      red notification "{N} service(s) still reference"
- [ ] Try delete an empty category → standard confirmation,
      then deletion succeeds

## ServiceResource (`/admin/services`)

- [ ] List shows ~40 seeded services
- [ ] Category column shows badge with category name
- [ ] base_price column shows ₹ prefix and formatted number
- [ ] Filter by category works
- [ ] Filter "Has duration" filters to services with non-null
      time_takes
- [ ] Try delete a service that has pricing rows → blocked
      with red notification
- [ ] Create a new service → category dropdown shows all
      active categories, slug auto-fills from name

## Sign-off

- [ ] All 30+ items checked above
- [ ] No console errors anywhere in admin panel
- [ ] No 500 errors in `php artisan serve` terminal output
- [ ] Phase 4.1 functions still work (login, logout, panel
      access gate)

Reply to architect: **"Phase 4.2 manual verification COMPLETE"**
or list specific failures with screenshots.
