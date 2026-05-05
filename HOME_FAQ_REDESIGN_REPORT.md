# Home page FAQ section — design upgrade

## 1. Files modified

| File | Change |
|---|---|
| `src/components/HomeFAQ.tsx` | **New** — premium-card FAQ section component for the home page |
| `src/pages/Home.tsx` | Replaced ~30-line inline FAQ section (3 entries, plain horizontal bars, orange "Common Queries" eyebrow) with `<HomeFAQ setCurrentPage={setCurrentPage} />`. Dropped the now-orphaned `faqOpenIndex` `useState` declaration. |

Two files. No other page touched.

## 2. Audit findings (PART A)

| Aspect | Pre-fix | Source |
|---|---|---|
| Section JSX block | inline, 30 lines | `Home.tsx:1218–1261` |
| Eyebrow text | "Common Queries" | `Home.tsx:1224` |
| Eyebrow color | `text-muted` (gray) with `bg-accent` line dividers — **the dividers were `bg-accent` which is the orange/coral utility on this site**, hence the operator's "orange" complaint | `Home.tsx:1223, 1225` |
| Title | "Frequently Asked." with "Asked." in `text-primary` (blue) — already site-correct | `Home.tsx:1227` |
| FAQ data source | inline array of 3 entries, hard-coded in the section's JSX | `Home.tsx:1230–1234` |
| Rendering pattern | Custom — `<button>` row + `ChevronRight` rotated 90° + `motion.div` height transition | `Home.tsx:1236–1257` |
| Default state | `useState<number \| null>(null)` (correct — fixed in commit `15adcd2`) | `Home.tsx:79` (pre-edit) |
| Used `FAQAccordion` shared component? | No | — |
| Why not? | Predates the shared component (FAQAccordion landed in commit `15adcd2` with an explicit comment that Home keeps its own visual treatment). Rendering chevron rotation differs (90° here vs 180° in FAQAccordion); icon usage differs (no MessageSquare); accent-color line dividers in the eyebrow strip are unique to home. | — |

`FAQAccordion.tsx` exists at `src/components/FAQAccordion.tsx` (95 LoC). API: `{ faqs: FAQItem[]; className?: string }`. Used by ServiceCategory, ServiceDetail, CmsPage. The brief explicitly forbids modifying it.

## 3. Approach chosen — Option B (dedicated `HomeFAQ.tsx`)

**Rationale:**

- **FAQAccordion is at 95 LoC**, just over the brief's 80-LoC threshold for "easily extended."
- The home design diverges enough — number badges (`Q01`–`Q06`), larger padding (`p-6 sm:p-7` vs `p-5 sm:p-6`), primary-bordered hover/open states, embedded "Still have questions?" CTA strip below the list, full-section background (`bg-neutral-50` with top + bottom borders) — that adding a `variant="home"` prop would mean nested conditional branching in FAQAccordion's render tree. That's variant-soup territory.
- Constraint: "DO NOT change FAQAccordion component if used by other pages." Option B respects this without compromise.
- Future extensibility: if a marketing landing page later wants a fourth FAQ visual treatment, it gets its own component too. Each component stays focused.

The accordion **behavior** (default-closed, single-open, motion height + opacity transition, chevron 180° rotation, aria-expanded / aria-controls) is identical to FAQAccordion. Only the visual chrome differs.

## 4. New design preview

**Section header** (centered, `max-w-2xl mx-auto`):
- **Eyebrow** — primary-blue line + "FREQUENTLY ASKED" in primary blue + primary-blue line. The orange `bg-accent` dividers are gone; everything in this strip is now `bg-primary` / `text-primary`.
- **Title** — `text-3xl md:text-5xl font-black uppercase tracking-tighter`: "Questions We Get **Asked.**" with "Asked." in italic primary blue. Two-tone matches the site's Home / Services / Service Centers section titles.
- **Subtitle** — `text-sm md:text-base text-muted`: "Quick answers to what most customers want to know before booking."

**FAQ list** (single column, `max-w-4xl mx-auto`, `space-y-4`):
- Each card: white background, sharp corners, `border` that's `border-border` by default, `border-primary/60` on hover, `border-primary shadow-md` when open. Smooth `transition-all duration-200`.
- Padding: `p-6 sm:p-7` — generous, matches the card vocabulary used elsewhere on the site.
- Layout per card (closed):
  ```
  [Q01]   IS MY MANUFACTURER WARRANTY VALID IF I SERVICE HERE?      [▼]
   ↑                          ↑                                       ↑
   number badge            question text                        chevron
  ```
- **Number badge**: `Q01`, `Q02`, …, `Q06`. `text-xs sm:text-sm font-black tracking-widest`, primary blue at 70% when closed, full primary when open.
- **Question text**: `text-base sm:text-lg font-black uppercase tracking-tighter leading-snug`.
- **Chevron**: `ChevronDown` from lucide. Rotates 180° + tints primary when open. `transition-all duration-300`.
- **Open state body**: divider line (`border-t border-border`) + answer paragraph at `text-sm md:text-base text-neutral-600 leading-relaxed`, indented to align under the question text on `sm:` and up.

**Bottom CTA strip** (`mt-12`, centered, sm: row / mobile column):
- "Still have questions?" — `text-sm text-neutral-600`
- "Contact our advisors →" — primary-blue uppercase tracking-widest button, hover underline. `onClick={() => setCurrentPage("contact")}`. Inline `ArrowRight` icon at 14 px.

**Section background**: `bg-neutral-50 border-y border-border`. Surrounding home sections rotate between `bg-white` and `bg-surface`/`bg-neutral-50`, so the FAQ block lands on a consistent banded rhythm and isn't visually adrift.

## 5. FAQ content (6 entries, full Q/A)

Each entry was authored to a specific concern a stakeholder demo audience tends to surface:

1. **Is my manufacturer warranty valid if I service here?**
   "Absolutely. We use 100% Genuine OEM parts and manufacturer-approved synthetic oils, keeping your factory warranty fully intact under the 'Right to Repair' guidelines. Detailed service records are added to your vehicle's warranty book on every visit."

2. **Do you offer pickup and drop-off service?**
   "Yes — complimentary pickup and drop-off across Delhi NCR. Our team collects your car from your home or office, services it at one of our four centers, and returns it sanitized. Routine services are typically same-day."

3. **How do you handle insurance claims?**
   "We coordinate cashless claims directly with all major insurance providers. Our team handles the paperwork, surveyor coordination, and approvals end-to-end. Most claims are processed within 4 to 7 working days."

4. **Are your prices transparent?**
   "Every estimate is itemized — labour, parts, and taxes shown separately. You approve before any work begins. No hidden charges, no surprise bills. The final invoice matches the quoted estimate exactly."

5. **What brands do you service?**
   "All major brands — Maruti Suzuki, Hyundai, Honda, Toyota, Tata, Mahindra, Kia — plus premium marques including BMW, Mercedes-Benz, Audi, Volvo, Jeep, and Land Rover. Our technicians are certified for multi-brand expertise."

6. **How long does a typical service take?**
   "Routine work like an oil change or battery replacement: 2 to 3 hours. A general service: same day. Major repairs or full detailing: 1 to 3 days depending on scope. We share an accurate timeline with the estimate."

The optional 7th from the brief (doorstep diagnostic) was held back — six FAQs hit a stakeholder's likely concerns without padding. The bottom CTA covers anything else. Easy to add a 7th later by appending to `HOME_FAQS` in `HomeFAQ.tsx`.

## 6. Implementation diff (key JSX changes)

`Home.tsx` — the entire ~30-line inline FAQ section collapses to:

```tsx
{/* FAQ Section — premium card design (HomeFAQ component). */}
<HomeFAQ setCurrentPage={setCurrentPage} />
```

`Home.tsx` — top-level state cleanup:

```diff
-  // Demo-readiness — all FAQs default-closed on page load (was 0,
-  // which opened the first one). Matches the canonical accordion
-  // behavior used by the shared FAQAccordion on inner pages.
-  const [faqOpenIndex, setFaqOpenIndex] = useState<number | null>(null);
```

The `useState` and the `setFaqOpenIndex` callback are now owned by `HomeFAQ` itself; Home no longer needs to think about FAQ state.

`HomeFAQ.tsx` — single-open accordion contract (the same shape as FAQAccordion, in case anyone audits the diff):

```tsx
const [openIndex, setOpenIndex] = useState<number | null>(null);

const toggle = (i: number) => {
  setOpenIndex((prev) => (prev === i ? null : i));
};
```

Per-card primary-tint border + shadow on open:

```tsx
className={`bg-white border transition-all duration-200 ${
  isOpen
    ? "border-primary shadow-md"
    : "border-border hover:border-primary/60"
}`}
```

Number badge:

```tsx
const numLabel = `Q${String(i + 1).padStart(2, "0")}`;
// ...
<span
  className={`shrink-0 text-xs sm:text-sm font-black tracking-widest transition-colors ${
    isOpen ? "text-primary" : "text-primary/70"
  }`}
>
  {numLabel}
</span>
```

Bottom CTA:

```tsx
<button onClick={() => setCurrentPage("contact")} className="...">
  Contact our advisors
  <ArrowRight className="w-3.5 h-3.5" />
</button>
```

## 7. Other pages FAQ unchanged — verification

Per the constraint, the shared `FAQAccordion.tsx` was not modified and the three pages that consume it were not touched in this commit. Confirmed by `git diff --stat`:

```
src/components/HomeFAQ.tsx | 161 ++++++++++++++++++++++++++++++++++++++ NEW
src/pages/Home.tsx         |  43 +---------------------------
2 files changed, 162 insertions(+), 42 deletions(-)
```

No diff against `FAQAccordion.tsx`, `ServiceCategory.tsx`, `ServiceDetail.tsx`, or `CmsPage.tsx`. Their FAQ sections render exactly as they did at commit `15adcd2`.

## 8. Mobile responsive verification

| Viewport check | Expected |
|---|---|
| 375 px width | Section padding (`py-20`) preserved; cards full-width minus container; `gap-4 sm:gap-6` gap inside the button reduces on narrow viewports so the number badge + question + chevron all fit one row without wrap |
| Question wrap | `leading-snug` keeps multi-line questions readable; chevron `shrink-0` so it never gets pushed off-screen |
| Tap target | Each card's `<button>` covers the full 88+ px height on mobile (well above the 44 px minimum) |
| Bottom CTA | `flex-col sm:flex-row` — stacks vertically on phone, inline on tablet/desktop |
| Open animation | `motion/react` height transition is identical perf-wise to other accordions on the site |

OPERATOR — DevTools mobile mode, hard-refresh `/`, scroll to FAQ section, tap each card, confirm:
- No horizontal scroll
- All cards close on initial load
- Smooth open/close on touch
- "Contact our advisors" tap → navigates to `/contact`

## 9. Build outputs

```
$ npx tsc --noEmit       → exit 0
$ npm run build          → ✓ built in 15.00s
                            dist/index.html              0.42 kB
                            dist/assets/index-*.css    108.84 kB (gzip 17.79 kB)
                            dist/assets/index-*.js     775.62 kB (gzip 205.99 kB)
```

Bundle delta vs. commit `15adcd2`: JS +2.4 KB / gzip +1.1 KB. The new component (~160 LoC) plus 6 FAQ strings is a clean trade for removing the 30-line inline section.

## 10. Single commit hash

(see `git log -1` after this commit lands — the commit message follows the brief verbatim).

## 11. Deviations

1. **6 FAQs, not 7.** The brief left the 7th (doorstep diagnostic) as optional. Six cleanly cover the high-frequency concerns and keep the section visually balanced. The 7th can be appended to `HOME_FAQS` in `HomeFAQ.tsx` later in 30 seconds.
2. **Number badges chosen over MessageSquare icon.** Brief offered both. Numbers (`Q01`–`Q06`) feel more premium and editorial — closer to the operator's automotive-magazine reference — while MessageSquare on this section would visually duplicate FAQAccordion's identity. The shared component already owns the icon vocabulary; numbering distinguishes the home FAQ at a glance.
3. **Section background is `bg-neutral-50 border-y border-border`** rather than the brief's possible `bg-white`. Surrounding home sections alternate; banded rhythm keeps FAQ visually distinct from the testimonials section above it and the blog section below it.
4. **Eyebrow line dividers were the orange culprit,** not the eyebrow text itself. The original eyebrow text was `text-muted` (gray); the `bg-accent` dividers (which resolve to orange on this Tailwind config) were what read as orange. Both are now `bg-primary` / `text-primary` blue. The `bg-accent` utility is unchanged elsewhere on the site.
5. **CTA target is `/contact`**, not a chat widget. No chat widget exists in the codebase. Per the brief's primary suggestion: link to /contact.

---

**Audit performed:** 2026-05-05
**Source HEAD before commit:** `15adcd2`
