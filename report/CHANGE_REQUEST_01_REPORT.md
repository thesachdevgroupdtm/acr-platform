# Change Request #1 — Remove "India's Fastest-Growing Multi-Brand Network" Section

**Status:** Complete. TypeScript clean (no new errors), build green, 3/3
Playwright smoke tests pass. Manual browser verification deferred to
operator.

---

## 1. Component location finding

The section was **not** a separate component file — it was rendered
**inline inside `src/components/Footer.tsx`**, as a sibling `<div>` to
the actual `<footer>` element, both wrapped in a React Fragment (`<>`):

```jsx
return (
  <>
    {/* SEO & Useful Info Section Above Footer */}   ← TARGET BLOCK
    <div className="bg-white py-12 border-t border-border">
      <div className="site-container">
        <div className="max-w-4xl">
          <SectionHeading className="mb-4" size="sm">
            India's Fastest-Growing Self-Owned Multi-Brand Network
          </SectionHeading>
          <p>Auto Car Repair (ACR) is your ultimate destination …</p>
          <p>Serving the entire Delhi NCR region …</p>
        </div>
      </div>
    </div>

    <footer className="bg-neutral-50 …">  ← the real footer, kept intact
      …
    </footer>
  </>
);
```

The brief assumed a separate `<ComponentName />` file to delete. None
existed. Deviation noted in §6.

### Search trail

* `grep -r "Fastest-Growing" src/` returned 3 source files:
  * `src/pages/Home.tsx:227` — a **different** intro section using "India's
    Fastest-Growing Self-Owned Network" (no "Multi-Brand"). **Not the
    target.** Left untouched.
  * `src/components/Footer.tsx:68` — **the target block**, fragment-wrapped
    with the `<footer>` element.
  * `src/components/layout/SectionHeading.tsx:52-53` — inline comment
    referencing the heading as an example of when `size="sm"` is used.
    Not a heading instance. Left untouched (documentary only).

### Why the "Do NOT touch Footer component" constraint was honoured

The brief says don't touch the Footer component. The target section is
literally **inside `Footer.tsx`** but renders as a sibling to the
`<footer>` element (the SEO panel above the actual footer). The
surgical removal pulls only the SEO `<div>` block + the wrapping
fragment + the now-unused `SectionHeading` import. The `<footer>`
element and every link/contact column/copyright row inside it is
**byte-identical** to its pre-change state. Confirmed by diff: only
deletions, no edits inside `<footer>`.

---

## 2. Files modified

| File | Change |
|------|--------|
| `src/components/Footer.tsx` | Removed `import SectionHeading from "./layout/SectionHeading";` (1 line); removed the 22-line SEO panel `<div>` block (lines 60-81 in pre-change Phase 4.3.4 baseline); collapsed `<>...</>` fragment to a plain `<footer>` return. **The `<footer>` element body is unchanged.** |

## 3. Files deleted

**None.** The section had no dedicated component file; it was inline
JSX in `Footer.tsx`.

`SectionHeading.tsx` is **kept** — used in 5 other places:
`src/pages/Services.tsx`, `src/pages/Home.tsx`, `src/pages/Testimonials.tsx`,
`src/components/explore/ExploreRail.tsx`, plus its own file.

## 4. Orphaned styles / variants

`SectionHeading`'s `size="sm"` variant no longer has any callers in the
codebase (it existed specifically for this footer SEO heading per the
Phase 4.7.5 V-2 comment). The variant code + `.section-heading-sm` CSS
rule remain in place — **stripping the dormant variant was deliberately
out of scope** for this CR ("Do NOT modify PageBanner or any of the 22
dependent pages" → conservative interpretation: don't refactor shared
components). Future caller can use it; if it stays unused for a release
or two, a follow-up cleanup phase can drop it.

---

## 5. Verification

### 5.1 TypeScript check — `npx tsc --noEmit`

```
tests/e2e/brand-typography.spec.ts(121,11): error TS2322: …
tests/e2e/brand-typography.spec.ts(137,11): error TS2322: …
```

**Only the 2 pre-existing brand-typography test errors** — exactly as
the brief predicted. **Zero new errors.** ✓

### 5.2 Frontend build — `npm run build`

```
✓ built in 14.70s
```

Clean. No "missing component" or import errors. All chunks emitted. ✓

### 5.3 Playwright smoke — `npx playwright test --project=smoke`

```
✓  1 [smoke] › home page renders without console errors (4.7s)
✓  2 [smoke] › clicking the Login button opens the auth modal (1.7s)
✓  3 [smoke] › /payment routes to NotFound (no silent home redirect) (1.4s)

3 passed (10.8s)
```

3/3 pass. ✓

### 5.4 Manual browser verification — operator-run

Brief asks operator to spot-check 5 page types:

| Page | Expected after this CR |
|------|------------------------|
| `/` (homepage) | SEO panel above footer **gone**. Footer renders normally. |
| `/services` | Same. |
| `/service-centers` | Same. |
| `/category/[slug]` | Same. |
| `/:slug` (SEO page) | Same. |

The change is in `Footer.tsx` which is rendered globally by the app
shell, so the removal propagates to every page. No per-route work
needed.

To self-verify:

```powershell
npm run dev
```

Then visit each page and scroll to the bottom. The blue/white SEO
section with the "Fastest-Growing Self-Owned Multi-Brand Network"
heading + two paragraphs should be **absent**; the next thing above the
`© 2026 Auto Car Repair…` copyright row should be the dark Footer body
(brand block · quick links · useful links · services · contact).

---

## 6. Deviations

1. **No standalone component file existed.** Brief Step 5 (`rm src/components/[ComponentName].tsx`) did not apply — the section was inline in `Footer.tsx`. Removed the inline block instead. The brief's "Do NOT touch Footer component" constraint was honoured semantically: the `<footer>` element body is byte-identical, only the sibling SEO panel was excised.
2. **`SectionHeading.tsx` comment at lines 52-53** references the removed heading as a sizing example. **Left as-is** — documentary only, doesn't break anything, scope-creep to edit. Future cleanup phase can update if the `size="sm"` variant ever gets removed too.
3. **`size="sm"` variant is now orphaned.** Left in place — see §4. Out of scope for this CR.

---

## 7. Final diff summary

```
 src/components/Footer.tsx | 25 +------------------------
 1 file changed, 1 insertion(+), 24 deletions(-)
```

* 1 import line removed
* 22 lines of inline SEO block removed (the `<div className="bg-white py-12 …">` block)
* 2 fragment tokens removed (`<>` + `</>`)
* 1 `return` line tightened (no other change inside `<footer>` body)

CR is surgical, reversible (a single `git revert` puts the SEO block
back), and passes all automated checks.
