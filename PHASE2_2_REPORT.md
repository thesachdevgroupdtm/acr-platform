# Phase 2.2 — User addresses (report)

Single-commit landing per the brief. Implements
`/PHASE2_CONTRACT.md` sections §2.3, §3 (Address model + User
relations to Address), §4.2 (AddressResource), §5.2 (4 endpoints),
§8 (auth middleware on all 4).

## Files created

### Backend
| File | Lines |
|---|---|
| `backend/database/migrations/2026_05_03_120001_create_addresses_table.php` | 40 |
| `backend/app/Models/Address.php` | 39 |
| `backend/app/Http/Resources/V1/AddressResource.php` | 32 |
| `backend/app/Http/Controllers/Api/V1/User/AddressController.php` | 174 |

### Frontend
None (types + fetchers added to existing files).

## Files modified

### Backend
| File | Change |
|---|---|
| `backend/app/Models/User.php` | Added `addresses()` HasMany and `defaultAddress()` HasOne (where is_default = true). HasOne / HasMany imports tightened. No schema or fillable change. |
| `backend/app/Http/Resources/V1/UserResource.php` | `default_address` now resolves via `whenLoaded('defaultAddress', …)`; returns AddressResource or null when relation is loaded, otherwise null (default). |
| `backend/app/Http/Controllers/Api/V1/User/ProfileController.php` | `show()` and `update()` eager-load `defaultAddress` so the populated `default_address` field appears on every `/user/profile` response. |
| `backend/routes/api.php` | Inside the existing `auth:sanctum` group, added 4 new routes (index/store/update/destroy) with throttle middleware. |

### Frontend
| File | Change |
|---|---|
| `src/types/api.ts` | Added `AddressInput`, `AddressesResponse`, `AddressResponse`. `AddressResource` was already declared in 2.1. |
| `src/lib/api.ts` | Added 4 typed fetchers: `fetchAddresses`, `postAddress`, `putAddress`, `deleteAddress`. |
| `src/hooks/useAuth.ts` | Replaced gated `addAddress` stub with the structured `addAddress(input: AddressInput)`. Added `listAddresses`, `updateAddress`, `deleteAddress`. New `flattenAddress()` and `serverList?` parameter on `presentUser()` so the consumer-facing `user.addresses` array reflects every row, not just default. |
| `src/pages/Checkout.tsx` | Removed legacy 3-arg `addAddress(string, label, makeDefault)` call site (free-form single-string address can't satisfy the structured 2.2 API). Comment points to the picker UI scheduled for 2.5. `addAddress` removed from the destructure. |

`src/components/Header.tsx`, `src/components/AuthModal.tsx`, and
`src/pages/MyBookings.tsx` were NOT modified — `Header` and
`MyBookings` only read `user.addresses` (compiles unchanged), and
`AuthModal` doesn't touch addresses at all.

## Migration output

```
$ php artisan migrate --force
INFO  Running migrations.
  2026_05_03_120001_create_addresses_table .......... 141ms DONE
```

## Schema verification (live MySQL)

```
mysql> SHOW COLUMNS FROM addresses;
+-------------+----------------------+------+-----+
| Field       | Type                 | Null | Key |
+-------------+----------------------+------+-----+
| id          | bigint unsigned      | NO   | PRI |
| user_id     | bigint unsigned      | NO   | MUL |
| label       | varchar(50)          | NO   |     |
| line1       | varchar(255)         | NO   |     |
| line2       | varchar(255)         | YES  |     |
| city        | varchar(80)          | NO   |     |
| state       | varchar(80)          | NO   |     |
| pincode     | varchar(10)          | NO   |     |
| landmark    | varchar(255)         | YES  |     |
| is_default  | tinyint(1)           | NO   |     |
| created_at  | timestamp            | YES  |     |
| updated_at  | timestamp            | YES  |     |
+-------------+----------------------+------+-----+
```

`(user_id, is_default)` composite index present.

## Route list (addresses subset)

```
GET|HEAD api/v1/user/addresses           Api\V1\User\AddressController@index
POST     api/v1/user/addresses           Api\V1\User\AddressController@store
PUT      api/v1/user/addresses/{address} Api\V1\User\AddressController@update
DELETE   api/v1/user/addresses/{address} Api\V1\User\AddressController@destroy
```

`php artisan route:list --path=api --json | jq length` → **27**
(16 existing + 7 from 2.1 + 4 from 2.2).

## Curl smoke chain

Token obtained via 2.1 lead-capture + verify-otp (bypass code):

```
$ POST /auth/lead-capture {name:"Phase 2.2 Tester", phone:"9988887777", email:"p22@example.com"}
HTTP 200 → dev_code 277224, pending_user_id 4

$ POST /auth/verify-otp {channel:"phone", destination:"9988887777", code:"1234"}
HTTP 200 → token "4|Q3pmMiLoHHPghRyYYjEjnU0ycwhm9K68093hii5374931f41"
```

### Address CRUD (Bearer-authenticated)

```
$ POST /user/addresses {line1:"A-1", city:"Delhi", state:"DL", pincode:"110001"}
HTTP 200
{"address":{"id":1,"label":"Home","line1":"A-1","line2":null,"city":"Delhi",
            "state":"DL","pincode":"110001","landmark":null,"is_default":true}}
       ↑ first-ever address auto-promoted to default ✓

$ GET /user/addresses
HTTP 200 → {"addresses":[{ id:1, …, is_default:true }]}

$ POST /user/addresses {label:"Office", line1:"B-2", city:"Delhi",
                        state:"DL", pincode:"110002", is_default:true}
HTTP 200
{"address":{"id":2,"label":"Office", …, "is_default":true}}

$ GET /user/addresses
HTTP 200 → {"addresses":[
  {"id":2, …, "is_default":true},
  {"id":1, …, "is_default":false}     ← single-default invariant enforced ✓
]}

$ PUT /user/addresses/1 {label:"Office"}
HTTP 200
{"address":{"id":1,"label":"Office", …, "is_default":false}}

$ GET /user/profile
HTTP 200 → user.default_address = { id:2, line1:"B-2", is_default:true, … }
       ↑ defaultAddress eager-load round-trips on profile fetch ✓

$ DELETE /user/addresses/2          (the current default)
HTTP 200 → {"success":true}

$ GET /user/addresses
HTTP 200 → {"addresses":[{ id:1, "is_default":true }]}
       ↑ surviving address auto-promoted to default ✓
```

### Negative cases

```
$ DELETE /user/addresses/999  (does not exist)
HTTP 404  {"message":"No query results for model [App\\Models\\Address] 999"}

$ POST /user/addresses {line1:"X", city:"D", state:"DL", pincode:"12"}
HTTP 422  {"errors":{"pincode":["The pincode field format is invalid."]}}

$ POST /user/addresses (no Authorization header)
HTTP 401  {"message":"Unauthenticated."}

$ PUT /user/addresses/1 {}
HTTP 422  {"message":"No fields to update"}

$ DELETE /user/addresses/1   using a DIFFERENT user's Bearer token
HTTP 404                                 ← ownership leak prevented ✓
```

## Frontend verification

```
$ npx tsc --noEmit
EXIT=0

$ npm run build
✓ 2152 modules transformed.
dist/index.html                    0.42 kB │ gzip:   0.28 kB
dist/assets/index-BlUgo5JH.css   104.77 kB │ gzip:  17.20 kB
dist/assets/index-BoZazcw_.js    729.96 kB │ gzip: 192.72 kB
✓ built in 27.97s
```

Browser DevTools smoke not driven from this session. Static check
confirms <Checkout> compiles without `addAddress` from the
destructure, <Header> still reads `user.addresses` (compiles), and
<MyBookings> never referenced addresses (no diff needed).

## Deviations

1. **`addAddress` signature change.** Phase 2.1 stubbed
   `addAddress(address: string, label?, makeDefault?)`. The 2.2
   replacement takes a structured `AddressInput` (line1, city, state,
   pincode separately). The single-string Checkout call site was
   removed with a comment pointing to 2.5 — the proper picker UI
   lands then. Header / MyBookings continued to compile because they
   only read `user.addresses`, not write.

2. **Discovered Phase 2.1 bug — `users.email NOT NULL`.** While
   creating a second user during the cross-user ownership test, the
   skeleton's NOT NULL `email` constraint blocked lead-capture when
   email is omitted (the contract says email is optional). The Phase
   2.1 smoke happened to always pass an email so the bug was masked.
   2.2 cannot fix this — the brief explicitly forbids modifying the
   `users` table — so for now lead-capture without email returns 500.
   Should be addressed by a Phase 2.1 fix-up migration that
   `change()`s `email` to nullable. **Workaround**: pass email in
   lead-capture until the schema is patched.

3. **`destroy` returns `200 {success:true}`** rather than `204 No
   Content`, matching `/auth/logout`'s pattern from 2.1 (the brief
   asked for consistency with logout).

4. **`is_default: false` on update of the current default is silently
   ignored** rather than 422'd. The invariant is "user with ≥1
   address always has exactly one default"; the only way to demote is
   to promote another address (which calls `demoteOthers`). Same
   behavior the contract implies via "exactly one default per user."

5. **`AddressController::index` returns `{ addresses: [...] }`**
   rather than Laravel's default `{ data: [...] }` from
   `JsonResource::collection()`. Consistent with the brief's typed
   shape `Promise<{ addresses: AddressResource[] }>`.

## Single commit

`a50475668b7b2441757e8f2b7f1ede20c0151c22` — 14 files, 702 inserts.
(Pre-amend hashes: `d39ea8a` → `f598228` → `a504756`. Each amend was
just to embed the previous hash into this report; the commit body
and tree are unchanged.)
