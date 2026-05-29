# Phase 4.5b-polish — Manual Verification Checklist

Run after architect signs off. Each item ~30 sec.

## Setup

1. Backend: `cd backend && php artisan serve --host=127.0.0.1 --port=8000`
2. Frontend: `npm run dev`
3. Confirm seeders ran: `php artisan db:seed --class=SeoPageMockSeeder`
   (idempotent — safe to re-run; total `SeoPage::count()` should
   be ≥ 14, with 4 marked `is_featured=true`)

## /explore — editorial layout

Visit <http://localhost:3000/explore>:

- [ ] Section 1 (Hero): single large card at top with featured
      page (Mercedes / BMW vs Audi / Luxury Detailing / Monsoon
      Tyres — 1 of 4)
- [ ] Section 2 (Pills): horizontal scrolling category chips
      below hero
- [ ] Section 3 (Trending Now): 1 FeatureCard + 2 StandardCards
      grid
- [ ] Section 4 (By Brand): 1 FeatureCard left + horizontal
      CompactCard rail right (5 mini-tiles)
- [ ] Section 5 (Service Guides): 4 HorizontalCards in 2-col grid
- [ ] Section 6 (All Articles): filter bar + paginated grid of
      StandardCards
- [ ] Cards have entry animations (fade-up stagger)
- [ ] Each section reveals on scroll into view
- [ ] Click any card → navigates to /:slug

## Filters wiring

- [ ] Click a category pill (Section 2) → smooth-scrolls to
      Section 6 with category filter applied
- [ ] Section 6 dropdown filter still works (Phase 4.5b contract)
- [ ] Search input still works in Section 6
- [ ] All Phase 4.5b/4.5b-fix data-testids preserved
      (`explore-card-{slug}`, `explore-category-filter`,
      `explore-search`, `explore-error`)

## /:slug — premium reading experience

Visit <http://localhost:3000/audi-service-delhi>:

- [ ] Reading progress bar fixed at top, fills as you scroll
- [ ] Breadcrumbs row: Home › Explore › Brand Service › Audi…
      (mobile collapses to "‹ Back to Explore")
- [ ] Hero with title + category badge + excerpt
- [ ] Below hero: meta row with author + date + read-time +
      clickable tag chips
- [ ] Click any tag chip → navigates to
      `/explore?search={tag}`
- [ ] Desktop only: right sidebar with:
  - [ ] "Need Service?" CTA card (NOT echoing the operator's
        full CTA title — the main CTA panel below the body
        carries that)
  - [ ] Auto-generated TOC from H2 headings
  - [ ] Share buttons (Twitter / LinkedIn / WhatsApp / Copy)
- [ ] Click TOC link → smooth-scrolls to that H2
- [ ] Click Copy Link → checkmark flashes for 1.5s
- [ ] Body uses premium typography (H2 uppercase
      tracking-tighter, 17px body, primary-amber links)
- [ ] CTA section after body (amber gradient panel)
- [ ] Related Articles grid (3 cards)
- [ ] Continue Reading section (HorizontalCards) at bottom

## Mobile

- [ ] /explore: hero collapses, pills row scrolls horizontally,
      grid sections stack to 1 column, By Brand rail scrolls
      with snap
- [ ] /:slug: no sidebar (sidebar is `lg:` only); reading
      progress bar still works; tag chips wrap

## Color palette

- [ ] Primary amber accents preserved
- [ ] Dark hero on /explore + /:slug
- [ ] Cards on white with amber-on-hover

## Sign-off

- [ ] All 25+ items checked
- [ ] No console errors
- [ ] No 500s in `php artisan serve` output

Reply: **"Phase 4.5b-polish manual verification COMPLETE"** or
list specific failures.
