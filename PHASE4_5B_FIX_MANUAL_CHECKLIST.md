# Phase 4.5b-fix — Manual Verification Checklist

Run after architect signs off. Each item ~30 sec.

## Setup

1. Backend running: `cd backend && php artisan serve --host=127.0.0.1 --port=8000`
2. Frontend running: `npm run dev`
3. `.env`: confirm `FRONTEND_URL=http://localhost:3000` (or
   whichever port your Vite picked — Phase 4.2.5b CORS regex
   covers 3000-3010).
4. Admin login: `admin@acr-mechanics.in / change-me-on-first-login`
5. Run `php artisan db:seed --class=SeoPageSeeder` if needed.

## Filament UX (notifications)

Visit <http://127.0.0.1:8000/admin/seo-pages>:

- [ ] Click "New SEO Page", fill required fields, click Create
- [ ] Toast appears: "SEO page created" with body explaining
      `is_published` toggle
- [ ] Edit any page, change a field, click Save
- [ ] Toast: "SEO page updated" with sitemap-cache note
- [ ] Submit invalid form (e.g. blank title) → inline errors
      show; no success toast
- [ ] Submit slug `cart` → red "reserved" error inline
- [ ] No duplicate seo_metadata rows after multiple saves
      (verify in tinker: `SeoMetadata::where('seoable_id', $id)->count()` returns 1)

## Preview action (Phase 4.5b-fix)

In list view AND on the Edit page:

- [ ] Preview button uses `heroicon-m-eye` icon and label
      "Preview"
- [ ] Click Preview → opens new tab to
      `http://localhost:3000/{slug}` (or whatever
      `FRONTEND_URL` is set to)
- [ ] In the new tab, the page renders (NOT NotFound)
- [ ] Address bar shows the customer-facing URL, NOT
      `/admin/...`
- [ ] If you stop the Vite server and click Preview again, the
      browser shows a "site can't be reached" — confirms
      Filament is sending the user to the customer host, not
      faking it via a Laravel route

## Search (relevance ranking)

On <http://localhost:3000/explore>:

- [ ] Type `audi` in search → Audi card appears first
- [ ] Type `warranty` → Audi Service page appears (the word
      lives only in the body of that page; relevance lifts it
      via searchable_text)
- [ ] Type `monsoon` → Monsoon Care Tips card appears
- [ ] Type `gurugram` → Gurugram AC card appears
- [ ] Type a single nonsense string (`zzzqq`) → empty state
- [ ] Clear search → all 4 seeded cards return

## Premium design (PART E refactor)

`/audi-service-delhi`:

- [ ] Dark hero header with breadcrumb trail
      (Home › Explore › Audi Service…)
- [ ] Last word of the title rendered in primary amber color
- [ ] Excerpt visible under the H1 in light gray
- [ ] Body has typographically rich rendering: H2 uppercase
      tracking-tighter, body 17px with comfortable line-height
- [ ] CTA section has gradient amber background, white text,
      "Ready when you are" badge, white CTA button
- [ ] Related Articles grid: 3 cards with category chip,
      title (line-clamp-2), excerpt, "Read More →" link
- [ ] Card hover: lifts 0.5px, border becomes amber-tinted,
      shadow grows; title turns primary; arrow slides right

`/explore`:

- [ ] Dark hero with "Explore *Articles*" oversized headline
- [ ] Description text under the H1
- [ ] Filter bar in white card with labeled inputs
      (CATEGORY / SEARCH)
- [ ] Search input has magnifying-glass icon
- [ ] Cards are 3-column on desktop, with category chip,
      title, excerpt, tag chips, "Read Article →"
- [ ] Cards animate in with staggered delay

## Mobile responsive

- [ ] /audi-service-delhi on mobile (DevTools): hero title
      shrinks to 4xl; body stays readable
- [ ] /explore on mobile: cards collapse to 1 column; filter
      bar wraps cleanly

## Sign-off

- [ ] All 30+ items checked
- [ ] No console errors
- [ ] No 500s in `php artisan serve` output
- [ ] Phase 4.5b base manual checklist still all green

Reply: **"Phase 4.5b-fix manual verification COMPLETE"** or
list specific failures with screenshots.
