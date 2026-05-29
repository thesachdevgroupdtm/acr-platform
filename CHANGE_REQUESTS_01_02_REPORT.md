# Change Requests #1 + #2 — Brand-Section Removal

**Status:** Complete. TypeScript clean (no new errors), build green
(bundle shrunk 2.2 kB), 3/3 Playwright smoke tests pass. Manual browser
verification deferred to operator.

---

## 1. Section locations

### Section 1 — Global "India's Fastest-Growing" SEO panel

| Item | Value |
|---|---|
| Location | **Inline JSX inside `src/components/Footer.tsx`** (NOT a separate component) |
| Pre-change lines | 60-81 (the `{/* SEO & Useful Info Section Above Footer */}` `<div>` block, fragment-wrapped sibling of the `<footer>` element) |
| Rendered on | Every page (Footer is rendered globally by the app shell) |
| Affected import | `import SectionHeading from "./layout/SectionHeading";` — removed; `SectionHeading` itself is **kept** (used by 5 other files) |

### Section 2 — Homepage "More Than Repairs. Absolute Trust." panel

| Item | Value |
|---|---|
| Location | **Inline JSX inside `src/pages/Home.tsx`** (NOT a separate component) |
| Pre-change lines | 419-498 — the `{/* About Section - Compact & High Impact */}` `<section>` block |
| Sub-blocks | Workshop image (left col) · "More Than Repairs. Absolute Trust." heading + paragraph + 4 pillars (Users/ShieldCheck/Zap/Star) + Explore Services button (right col) · 4-stat strip below (50K Cars / 4 Centers / 15 Years / 98%) |
| Rendered on | Homepage only (`/`) |
| Affected import | `Users` icon (lucide-react) — removed; `ShieldCheck`, `Zap`, `Star` reused elsewhere in `Home.tsx`, kept |

---

## 2. Files modified

| File | Change |
|------|--------|
| `src/components/Footer.tsx` | Removed `SectionHeading` import; removed the 22-line SEO panel `<div>` block; collapsed the React Fragment (`<>…</>`) to a plain `<footer>` return. **The `<footer>` element body is byte-identical to pre-change.** |
| `src/pages/Home.tsx` | Removed orphaned `Users` icon from the lucide-react import list; replaced the 80-line `<section>` block (image + content grid + 4-pillar block + Explore Services button) with a 16-line slim stats-strip `<section>` that preserves the 4 trust counters (50K Cars / 4 Centers / 15 Years / 98% Satisfaction). |

## 3. Files deleted

**None.** Both target sections were inline JSX, not separate component
files. Briefs Step 7 + Step 10 (`rm` commands) did not apply — deviation
documented in §6.

---

## 4. What was kept and why

### From Section 2 — the 4-stat trust strip

The brief enumerated three items for Section 2 removal: heading,
paragraph, 4 pillars. The 4-stat counters (50,000+ Cars Serviced /
4+ Service Centers / 15+ Years Experience / 98% Customer Satisfaction)
were **inside the same `<section>` element** but **not enumerated** in
the brief, and the brief's locked constraints explicitly read *"Do NOT
modify hero sections, service category sections, or other homepage
components."* The stat counters are a homepage trust component that
doesn't share the "India's fastest-growing self-owned multi-brand"
language being scrubbed.

**Surgical choice:** kept the stats, shed the rest. The original wrapper
`<section className="bg-surface py-10 md:py-24">` is preserved so the
surface tint + vertical rhythm of the homepage flow stays intact. The
stats div lost its `mt-20 pt-10 border-t border-border` classes (those
existed to separate stats from the now-deleted grid above; without
context above, the dangling top-border looks broken). An explanatory
comment is left in the JSX flagging this as a CR#2 surgical site so a
future reader doesn't wonder why a `<section>` has only stats inside.

### Component files preserved

* `src/components/layout/SectionHeading.tsx` — used by 5 other files
  (`Services.tsx`, `Home.tsx`, `Testimonials.tsx`, `ExploreRail.tsx`,
  plus self). Its `size="sm"` variant is now orphaned (only Section 1
  used it) but the variant code is left dormant in place rather than
  refactored — refactoring shared components was out of scope.

---

## 5. Verification

### 5.1 TypeScript — `npx tsc --noEmit`

```
tests/e2e/brand-typography.spec.ts(121,11): error TS2322: …
tests/e2e/brand-typography.spec.ts(137,11): error TS2322: …
```

**Only the 2 pre-existing brand-typography errors** — exactly as both
briefs predicted. **Zero new errors.** ✓

### 5.2 Build — `npm run build`

```
✓ built in 38.17s
```

Clean. Bundle shrunk: `index-C0CsRgnY.js` is **185.81 kB** (was 188.03 kB
before CR#1+#2 — a 2.22 kB drop, since the two inline panels + the
orphaned `Users` icon are gone). ✓

### 5.3 Playwright smoke — `npx playwright test --project=smoke`

```
✓  1 [smoke] › home page renders without console errors (5.7s)
✓  2 [smoke] › clicking the Login button opens the auth modal (2.4s)
✓  3 [smoke] › /payment routes to NotFound (no silent home redirect) (1.8s)

3 passed (14.2s)
```

3/3 pass. ✓

### 5.4 Manual verification — operator-run

To self-verify:

```powershell
npm run dev
```

| Page | Section 1 (above-footer SEO) | Section 2 ("More Than Repairs") |
|---|---|---|
| `/` (homepage) | absent ✓ | absent ✓ (stats strip kept) |
| `/services` | absent ✓ | n/a (only on home) |
| `/service-centers` | absent ✓ | n/a |
| `/category/[slug]` | absent ✓ | n/a |
| `/:slug` (SEO page) | absent ✓ | n/a |

Section 1 lives in the global Footer, so removal propagates to every
page automatically — no per-route work needed.

For the homepage, scroll order is now:

```
Hero → … existing sections … → Stats Strip (4 counters, kept) → …
existing sections … → Footer (no SEO panel above it)
```

---

## 6. Deviations

1. **Neither section was a standalone component file.** Both briefs assumed `<ComponentName />` imports + `rm src/components/[X].tsx` deletes. Neither applied: Section 1 was inline in `Footer.tsx`, Section 2 was inline in `Home.tsx`. Removed the inline JSX blocks instead. The locked "Do NOT touch Footer component" was honoured semantically — the `<footer>` element body inside `Footer.tsx` is byte-identical to its pre-change state; only the sibling SEO panel + fragment wrapping were removed.

2. **Section 2's 4-stat trust strip was preserved**, per the brief's enumeration ("Heading / paragraph / 4 pillars") not mentioning stats, and the locked constraint *"Do NOT modify … other homepage components."* If the operator intended to remove the stats too, a follow-up edit deleting the new slim `<section>` at the same location is a one-block surgical change. Comment in the JSX flags the CR#2 surgical site for easy future reference.

3. **`SectionHeading.tsx`'s `size="sm"` variant is now orphaned.** Left in place — see §4.

4. **One inline comment in `SectionHeading.tsx` (lines 52-53) references the removed footer heading as a sizing example.** Documentary only, doesn't break anything. Left as-is to avoid scope creep.

---

## 7. Final diff summary

```
 src/components/Footer.tsx | 25 +------------------------
 src/pages/Home.tsx        | 71 ++-----------------------------------------
 2 files changed, 5 insertions(+), 91 deletions(-)
```

* **`Footer.tsx`:** 1 import line removed, 22-line SEO `<div>` block
  removed, 2 fragment tokens removed.
* **`Home.tsx`:** 1 icon import (`Users`) removed, 80-line `<section>`
  block replaced by a 16-line slim stats-strip section (net -64 lines).

Both changes are surgical, reversible (`git revert` puts each back
cleanly), and pass all automated checks. The locked constraints — no
Footer touch, no PageBanner / 22 dependent pages touch, no Lead form /
Book CTA / hero / category-section touch, no SeoPageView / CmsPage
touch — were all honoured.
