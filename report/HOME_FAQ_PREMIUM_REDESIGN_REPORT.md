# Home FAQ section v2 — premium dark redesign

## 1. Files modified

| File | Change |
|---|---|
| `src/components/HomeFAQ.tsx` | Replaced v1 light/empty card-on-gray section with v2 dark image-backed treatment. Same component name, same `setCurrentPage` prop, same six FAQ entries, same accordion contract — only the visual chrome changed. |

One file. No other page touched. `Home.tsx` already mounts `<HomeFAQ setCurrentPage={setCurrentPage} />` from the v1 commit; that wiring stays.

## 2. Visual approach — dark image bg + frosted FAQ cards

**Three stacked background layers**, all `absolute inset-0 z-0`:

1. **Image** — full-bleed `object-cover` automotive workshop photograph. Sharp (no blur applied — the overlay does the legibility work).
2. **Dark gradient overlay** — `bg-gradient-to-br from-black/90 via-black/80 to-primary/30`. Heavy black on the upper-left where the title lives, fading toward a primary-tinted bottom-right so the section reads as branded ACR rather than generic dark.
3. **Accent vignette** — radial primary tint anchored to the top-right corner via inline `style` (Tailwind doesn't have a built-in primary-tinted radial). Adds depth without competing with the FAQ cards.

**Content layer** sits at `relative z-10` on `max-w-5xl mx-auto px-6`.

**FAQ cards** flip between two visual states on toggle:

- **Closed** — `bg-white/5 backdrop-blur-md border border-white/10`. Frosted-glass over the dark image, white text + chevron in `text-white/60`. Hover lifts to `bg-white/10 + border-primary` for visible feedback on the dark backdrop.
- **Open** — `bg-white border-2 border-primary shadow-2xl`. Solid white card with a primary-blue 2px border + a heavy shadow, lifting it visually above the image. Text colors invert: question is now `text-neutral-900`, number badge is full `text-primary`, chevron is `text-primary rotate-180`.

The state-flip on toggle (translucent → solid) is the core "premium" move and what makes the section read as intentional rather than empty. No transition is needed on the card chrome itself — `transition-all duration-300` handles the interpolation between the two `className` blobs.

## 3. Background image source

| | |
|---|---|
| Source | Unsplash |
| Photo ID | `photo-1486262715619-67b85e0b08d3` |
| URL | `https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&w=1920&q=80` |
| Subject | Mechanic working on a vehicle in a workshop — automotive feel, professional, non-generic |
| Sizing | 1920px max width via `?w=1920`, optimized webp via `?auto=format`, 80% quality |
| Loading | `loading="lazy"` (section is below fold on home page) |
| Decorative | `alt=""` + `aria-hidden="true"` — the section has its own headline; the image is purely atmospheric |

This URL is the same Unsplash photo used by the Moti Nagar service-center hero in `LOCATIONS` (`businessData.ts`), so the browser cache hit on a typical home → service-centers navigation is free. If the operator wants visual variety, swap to `photo-1503376780353-7e6692767b70` (workshop interior) — the brief offered both options.

## 4. Section structure preview

The full JSX is in `src/components/HomeFAQ.tsx` (179 lines). Key skeleton:

```tsx
<section className="relative py-20 sm:py-24 lg:py-28 overflow-hidden">
  {/* Layer 1 — image */}
  <div className="absolute inset-0 z-0">
    <img src={BG_IMAGE_URL} ... className="w-full h-full object-cover" loading="lazy" />
  </div>

  {/* Layer 2 — dark gradient overlay */}
  <div className="absolute inset-0 z-0
                  bg-gradient-to-br from-black/90 via-black/80 to-primary/30" />

  {/* Layer 3 — primary radial vignette */}
  <div className="absolute inset-0 z-0"
       style={{ backgroundImage: "radial-gradient(circle at top right, rgba(31,79,163,0.18), transparent 55%)" }} />

  {/* Content */}
  <div className="relative z-10 max-w-5xl mx-auto px-6">
    {/* Header (eyebrow + two-tone title + subtitle) */}
    {/* FAQ list — max-w-3xl, 6 cards */}
    {/* Bottom CTA — "Contact our advisors" button */}
  </div>
</section>
```

The eyebrow ("FREQUENTLY ASKED") is in `text-primary` blue with primary-blue line dividers — the orange `bg-accent` dividers from v0 are gone, the gray-text + primary-line-divider compromise from v1 is gone too. Everything in the eyebrow is now consistent primary blue.

The title is `text-3xl sm:text-5xl lg:text-6xl font-black uppercase tracking-tighter text-white` — significantly larger than v1's `text-3xl md:text-5xl` because the dark dramatic backdrop wants more typographic weight. "ASKED." stays italic primary blue.

## 5. State transitions — closed translucent → open solid white

The accordion contract is identical to v1:

```ts
const [openIndex, setOpenIndex] = useState<number | null>(null);

const toggle = (i: number) => {
  setOpenIndex((prev) => (prev === i ? null : i));
};
```

Per-card className uses a ternary on `isOpen`:

```tsx
className={`transition-all duration-300 ${
  isOpen
    ? "bg-white border-2 border-primary shadow-2xl"
    : "bg-white/5 backdrop-blur-md border border-white/10 hover:bg-white/10 hover:border-primary"
}`}
```

Per-element color flips:

| Element | Closed | Open |
|---|---|---|
| Card bg | `bg-white/5` (frosted) | `bg-white` (solid) |
| Card border | `border border-white/10` | `border-2 border-primary` |
| Card shadow | none | `shadow-2xl` |
| Number badge `Q01–Q06` | `text-primary/70` | `text-primary` |
| Question text | `text-white` | `text-neutral-900` |
| Chevron | `text-white/60` | `text-primary rotate-180` |
| Answer paragraph | not rendered | `text-neutral-600` on white |

The answer block uses `motion/react` `<AnimatePresence initial={false}>` with `height: 0 → auto` + `opacity: 0 → 1` over 300 ms `easeOut` — same animation package v1 used. The brief's plain `{isOpen && ...}` snippet was instant; using motion preserves the smooth feel and matches the pattern already in use elsewhere on the site.

`aria-expanded` and `aria-controls` are set on each toggle button, mirroring the FAQAccordion shared component's accessibility contract.

## 6. Mobile responsive

Verified statically against the Tailwind breakpoints:

| Viewport | Behavior |
|---|---|
| 375 px (mobile) | Section padding `py-20` (no `sm:` larger version applies); title `text-3xl` (drops one tier from desktop's `text-6xl`); cards full-width inside `max-w-3xl mx-auto`; question + number-badge gap `gap-4` keeps row from wrapping; chevron `shrink-0`; CTA button `inline-flex` stays single-line |
| 640 px (sm:) | Section padding `py-24`; cards switch to `gap-5` and `p-6`; bottom CTA button gains `text-xs` clarity |
| 1024 px (lg:) | Section padding `py-28`; title hits its `text-6xl` ceiling |
| Touch target | Each card `<button>` covers full row at `p-5` minimum on mobile = ~76 px height (above the 44 px minimum) |
| Backdrop blur | `backdrop-blur-md` is widely supported in modern browsers; older browsers see the `bg-white/5` semi-transparent fallback unblurred — still readable, no extra fallback needed |

OPERATOR — DevTools mobile, hard-refresh `/`, scroll to FAQ, tap each card, confirm:
- Background image scales without horizontal scroll
- Frosted cards remain legible against the image
- Tap-to-open works on touch (no hover requirement)
- Open state's solid white card pops clearly against the dark backdrop
- "Contact our advisors" button reachable; tap → `/contact`

## 7. Build outputs

```
$ npx tsc --noEmit       → exit 0
$ npm run build          → ✓ built in 33.03s
                            dist/index.html              0.42 kB
                            dist/assets/index-*.css    111.03 kB (gzip 17.98 kB)
                            dist/assets/index-*.js     776.37 kB (gzip 206.27 kB)
```

Bundle delta vs. v1 (commit `eff2212`): JS +0.75 KB / gzip +0.28 KB; CSS +2.19 KB / gzip +0.19 KB. The CSS bump is the new utility classes (`bg-white/5`, `backdrop-blur-md`, the radial-gradient inline style doesn't add CSS but the supporting Tailwind colors do). Within budget.

## 8. Single commit hash

(see `git log -1` after this commit lands)

## 9. Things to monitor in production

1. **Image load time on slow networks.** The Unsplash URL is 1920px / q=80; on a Slow 3G connection the image is ~120-200 KB and may take 1-2s to land. The `loading="lazy"` attribute defers the request until the section approaches the viewport, so this hits *only* a user who actually scrolls to the FAQ. The `bg-gradient-to-br from-black/90` overlay paints on the section's own background regardless, so even with the image still loading the section renders dark + readable rather than as an empty white box.

2. **`backdrop-blur-md` browser support.** All evergreen browsers (Chrome, Edge, Firefox, Safari 14+) support it. Older Safari (< 14) and iOS < 14 see no blur — they still get `bg-white/5` (semi-transparent white). The card is still distinguishable from the dark backdrop. No polyfill needed.

3. **Text contrast over varied image regions.** The image has a wide dynamic range; the dark gradient overlay (black 80–90%) ensures all text and UI remains AA-contrast even over the brightest parts of the photo. If the operator swaps the image, retest contrast on the new image.

4. **Hover states on touch devices.** `hover:bg-white/10 hover:border-primary` won't fire on touch. The closed-state visual feedback for tap is the open-state transition itself — adequate for touch but worth noting.

5. **Print stylesheet.** `bg-white/5` + `backdrop-blur-md` print as effectively transparent. If anyone tries to print the home page, the FAQ section will be unreadable. Out of scope but worth flagging.

## 10. Deviations from the brief

1. **Animation library:** brief offered plain `{isOpen && ...}` (instant show/hide) OR motion lib. Used `motion/react` `AnimatePresence` with height + opacity transition — same pattern v1 used and same package consumed elsewhere on the site. Smooth motion was the v1 baseline; downgrading to instant in v2 would feel like a regression even though the chrome is "more premium."

2. **Background image:** picked `photo-1486262715619-67b85e0b08d3` (the brief's first option) over `photo-1503376780353-7e6692767b70` (second option). Reasoning: the first photo is already used by the Moti Nagar service-center hero in `LOCATIONS` and is therefore browser-cached on most home → service-centers navigations. Second-best option is a one-line constant change at `BG_IMAGE_URL` in `HomeFAQ.tsx` if the operator prefers variety.

3. **Bottom CTA:** brief showed two button styles (`btn-ink btn-ink-white` button OR plain primary-text underlined link). Used the `btn-ink-white` button — reads as a real CTA on the dark backdrop and matches the bottom-CTA pattern used by the Testimonials page (`feat: testimonials` commit `6621452`). Stakeholder demo continuity matters here: same visual vocabulary across "what's next" cues.

4. **CTA copy structure:** brief showed "Still have questions? CONTACT OUR ADVISORS →" inline. Split it into two lines (subtitle text + button) for visual rhythm — single-line on the dark backdrop made the CTA cluster too dense. The layout is `text-center mt-12` with the subtitle above the button, both centered.

5. **Eyebrow size scale:** brief specified `text-4xl sm:text-5xl lg:text-6xl` for the title. Used `text-3xl sm:text-5xl lg:text-6xl` (one tier smaller on mobile) so it doesn't crowd small-screen viewports. The brief's `text-4xl` mobile size would push the headline past 4 lines on a 375px viewport given its length; `text-3xl` keeps it to 2 lines.

6. **Inline style for radial vignette.** Tailwind has no built-in radial-gradient utility for primary-tinted vignettes; used a one-line `style={{ backgroundImage: "radial-gradient(...)" }}` rather than a custom plugin. Single use, no need for global config churn.

---

**Audit performed:** 2026-05-05
**Source HEAD before commit:** `eff2212`
