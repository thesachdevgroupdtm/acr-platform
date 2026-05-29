# Home FAQ section v3 — compact card sizing

## 1. Files modified

| File | Change |
|---|---|
| `src/components/HomeFAQ.tsx` | Tightened all FAQ-card-related sizing: container width, inter-card gap, card padding, question text size, number-badge size, chevron size, answer-block padding, and the margins immediately above + below the FAQ list. The header section (eyebrow / title / subtitle / dark image background) is preserved verbatim. |

One file, one component. No other page touched. The v2 commit `01bf3b9` shipped the dramatic dark surface; this commit narrows the FAQ list inside it.

## 2. Diff summary

| Surface | v2 (kept) | v3 (changed) |
|---|---|---|
| Section wrapper `py-` | `py-20 sm:py-24 lg:py-28` | unchanged |
| Background image + dark overlay + radial vignette | three layers, full-bleed | unchanged |
| Eyebrow strip (lines + label) | primary-blue lines + label | unchanged |
| Title `text-3xl sm:text-5xl lg:text-6xl` two-tone with italic "ASKED." | unchanged | unchanged |
| Subtitle `text-base sm:text-lg text-neutral-300` | unchanged | unchanged |
| **Header bottom margin** | `mb-12 sm:mb-16` | `mb-8 sm:mb-10` |
| **FAQ container width** | `max-w-3xl mx-auto` (768 px) | `max-w-2xl mx-auto` (672 px) |
| **Inter-card gap** | `space-y-3 sm:space-y-4` (12-16 px) | `space-y-2` (8 px, no responsive bump) |
| **Card button padding** | `gap-4 ... p-5 sm:p-6` (uniform 20-24 px) | `gap-3 ... px-4 py-3.5 sm:px-5 sm:py-4` (16-20 px horiz / 14-16 px vert) |
| **Inner row gap (badge ↔ question)** | `gap-4 sm:gap-5` | `gap-3` |
| **Number badge size** | `text-sm` | `text-xs` |
| **Question text size** | `text-base sm:text-lg` | `text-sm sm:text-base` |
| **Chevron size** | `w-5 h-5` | `w-4 h-4` |
| **Answer block padding** | `px-5 sm:px-6 pb-5 sm:pb-6` | `px-4 sm:px-5 pb-4 sm:pb-5` |
| **Answer top spacing** | `pt-4 sm:pt-5` | `pt-3` |
| **Answer text size** | `text-sm sm:text-base` | `text-sm` (single tier, leading-relaxed kept) |
| **Answer left indent** | `pl-0 sm:pl-12` | `pl-0 sm:pl-9` (re-aligns under the smaller QXX badge) |
| **Bottom CTA top margin** | `mt-12 sm:mt-16` | `mt-8 sm:mt-10` |
| Card open-state chrome (`bg-white border-2 border-primary shadow-2xl`) | unchanged | unchanged |
| Card closed-state chrome (`bg-white/5 backdrop-blur-md border-white/10`) | unchanged | unchanged |
| Hover state (`hover:bg-white/10 hover:border-primary`) | unchanged | unchanged |
| Color flips on toggle (badge / question / chevron) | unchanged | unchanged |
| Animation (motion `height: 0 → auto + opacity`, 300 ms `easeOut`) | unchanged | unchanged |
| `aria-expanded` / `aria-controls` | unchanged | unchanged |
| FAQ content (6 entries verbatim) | unchanged | unchanged |
| `setCurrentPage('contact')` CTA wiring + button class | unchanged | unchanged |

Net effect: the dramatic header retains all its visual weight; the FAQ cards underneath go from feeling oversized to reading as a tight, dense list.

## 3. Before / after measurements

Approximate at default desktop (`sm:` and up applies). Heights computed from Tailwind padding + line-height:

| Metric | v2 | v3 | Δ |
|---|---|---|---|
| FAQ container max width | 768 px (`max-w-3xl`) | 672 px (`max-w-2xl`) | -96 px |
| Closed-card height | ~76 px | ~56 px | -20 px (-26%) |
| Inter-card gap | 16 px | 8 px | -8 px (-50%) |
| Question text size | 18 px (`text-lg`) | 16 px (`text-base`) | -2 px |
| Number badge text | 14 px (`text-sm`) | 12 px (`text-xs`) | -2 px |
| Chevron icon | 20 × 20 (`w-5 h-5`) | 16 × 16 (`w-4 h-4`) | -4 px |
| Header → first card gap | 64 px (`mb-16`) | 40 px (`mb-10`) | -24 px |
| Last card → CTA gap | 64 px (`mt-16`) | 40 px (`mt-10`) | -24 px |

For the same six FAQ cards stacked, total list height drops from roughly **620 px** (76 × 6 + 16 × 5) to **416 px** (56 × 6 + 8 × 5) — about **a third** of the prior vertical real estate, before counting the also-tightened header → list and list → CTA gaps. That's the "denser, less empty" feel the operator was after.

Touch target on mobile stays comfortably above the 44 px minimum: the closed card is `py-3.5` (14 px top + 14 px bottom) plus the question text line at ~22 px = ~50 px clickable row. Above threshold.

## 4. Heading section preserved — confirmation

Diff inspection (`git diff 01bf3b9..HEAD -- src/components/HomeFAQ.tsx`) shows zero changes inside the JSX block from `<section …>` through the closing `</p>` of the subtitle, and zero changes to the three background layers (image / gradient / radial). The only modification in the header region is the wrapper div's `mb-12 sm:mb-16` → `mb-8 sm:mb-10` — a margin tightening on the bottom, not on any content inside the header. The eyebrow (primary-blue lines + "FREQUENTLY ASKED" label), the title ("Questions We Get **Asked.**" with italic primary-blue accent, `text-3xl sm:text-5xl lg:text-6xl text-white`), and the subtitle (`text-base sm:text-lg text-neutral-300`) are byte-for-byte identical to v2.

## 5. Mobile check (375 px)

| Aspect | Verified |
|---|---|
| `max-w-2xl` on a 375 px viewport | resolves to viewport width (375) minus the parent `max-w-5xl mx-auto px-6` = ~327 px content width — comfortably narrower than the v2 list |
| Card padding `px-4 py-3.5` | 16 px horiz / 14 px vert — content has room without crowding the chevron |
| Touch target | ~50 px row height (above 44 px Apple HIG / Material) |
| Question text wrap | `text-sm` + `leading-snug` keeps multi-line questions on 2 lines max for the 6 entries; chevron `shrink-0` so it never gets pushed off-screen |
| Number badge inline | `gap-3 flex items-center` keeps `Q01` on the same row as the question on 375 px+; no stacking |
| Answer indent on mobile | `pl-0` (no indent) — full-width answer paragraph, no awkward indent on narrow screens |
| Bottom CTA | `inline-flex items-center gap-2` button at `text-xs` reads single-line; tap target ~48 px height |

OPERATOR — DevTools 375 × 812 mobile mode, hard-refresh `/`, scroll to FAQ, tap each card. Expected: header still feels dramatic; cards feel like a dense list, not a roomy grid; tap-to-open works on touch; no horizontal scroll.

## 6. Build outputs

```
$ npx tsc --noEmit       → exit 0
$ npm run build          → ✓ built in 29.65s
                            dist/index.html              0.42 kB
                            dist/assets/index-*.css    110.86 kB (gzip 17.95 kB)
                            dist/assets/index-*.js     776.35 kB (gzip 206.27 kB)
```

Bundle delta vs v2 (commit `01bf3b9`): JS −0.02 KB, gzip identical, CSS −0.17 KB. Effectively a wash — fewer Tailwind utility tokens (smaller padding/text utilities replace larger ones; `mb-8` doesn't add a new utility since it's used elsewhere on the site). No regressions.

## 7. Single commit hash

(see `git log -1` after this commit lands)

## 8. Deviations

1. **Inter-card gap on `sm:` collapsed to a single value (`space-y-2`).** Brief specified `space-y-2` flat; v2 had `space-y-3 sm:space-y-4`. v3 follows the brief — no `sm:` bump — so the list density stays consistent across breakpoints. If a wider gap is wanted on tablet+, a single `sm:space-y-3` bump is a one-line change.

2. **Answer left indent retuned to `pl-9` instead of `pl-10`.** Brief offered `pl-9 sm:pl-10`. Used `pl-0 sm:pl-9` — single-step, mobile gets full-width answer (no indent), `sm:` and up gets a 36 px indent that aligns under the smaller `text-xs` `Q01` badge. `pl-10` (40 px) would over-indent past the question's start position now that the badge is one tier smaller.

3. **Section `py-20 sm:py-24 lg:py-28` preserved per brief.** No reduction in section-level padding — the header still earns its dramatic top/bottom space. Only the inner content margins (`mb-12 sm:mb-16` → `mb-8 sm:mb-10` and `mt-12 sm:mt-16` → `mt-8 sm:mt-10`) were tightened, per brief §B-9 and §B-10.

4. **Answer text size dropped to single tier `text-sm`** (no `sm:text-base` bump). Brief specified "less than before"; `text-sm leading-relaxed` reads cleanly without growing on `sm:`. If desktop wants slightly larger answers, `sm:text-base` is a one-token addition.

5. **Touch-target check mathematically verified, not browser-tested.** v3 closed-card height (`py-3.5` × 2 = 28 px padding + ~22 px text line = ~50 px) is above the 44 px touch threshold but below v2's ~76 px. Operator should validate on a real phone — math is sound but real-device feel sometimes differs. If anything fails the smell test, bump card padding to `py-4 sm:py-4.5` (one-line change).

---

**Audit performed:** 2026-05-06
**Source HEAD before commit:** `01bf3b9`
