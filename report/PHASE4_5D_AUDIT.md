# Phase 4.5d — Pre-flight Audit (PART A)

## 1. Legacy `src/lib/SeoHead.tsx` importers

```
grep -rn "from.*lib/SeoHead\|import.*SeoHead.*lib\|lib/SeoHead" src/
src/components/SeoHead.tsx:8: * snake_case). The legacy `src/lib/SeoHead.tsx` (Phase 1.6) is …
```

Only a **doc comment** in `src/components/SeoHead.tsx` mentions the
legacy file — no `import` statements anywhere. Safe to delete in
PART B. The doc comment will be lightly updated to remove the
"kept untouched" wording once the file is gone.

## 2. SchemaTemplateEngine — current FAQPage state

`backend/app/Services/SchemaTemplateEngine.php:131` already has a
`faqPage()` template that **reads from `$seo->schema_data['faqs']`**.
If the operator manually pastes a `faqs` array into the SEO record's
schema_data field, the engine renders a valid FAQPage JSON-LD. But
in practice no record carries that nested array, so FAQPage
currently always returns null.

```php
protected function faqPage(SeoMetadata $seo): ?array
{
    $faqs = $seo->schema_data['faqs'] ?? [];
    if (empty($faqs)) {
        // Phase 4.5d will plumb FAQs from a dedicated source;
        // until then, render only when schema_data carries
        // an explicit list.
        return null;
    }
    // … emits FAQPage with mainEntity[]
}
```

The stub is well-placed — Phase 4.5d just needs to plumb a real
data source through it.

## 3. FAQ data source audit

```
ls backend/app/Models/ | grep -i faq         → no matches
ls backend/database/migrations/ | grep -i faq → no matches
grep -rn "HOME_FAQS\|QUESTIONS WE GET" src/  → matches in HomeFAQ.tsx + Home.tsx
```

FAQs are **hardcoded** in `src/components/HomeFAQ.tsx:28-53` as a
`HOME_FAQS` const — 6 Q/A pairs covering warranty validity,
pickup/drop, insurance claims, pricing transparency, supported
brands, and turnaround.

**Decision: Path B** — build minimal FAQ infrastructure.

### Time estimate

| Step | Estimate |
|------|----------|
| migration: `create_faqs_table` (id, question, answer, sort_order, is_active, timestamps) | 5 min |
| seeder: extract the 6 hardcoded Q/A pairs verbatim | 10 min |
| `Faq` Eloquent model | 3 min |
| `FaqResource` (simple CRUD with sort_order column) | 25 min |
| bind `SchemaTemplateEngine::faqPage()` to read from `Faq::query()` | 10 min |
| `/api/v1/faqs` GET endpoint | 10 min |
| 3 tests (model save, FAQPage JSON-LD output, endpoint shape) | 15 min |
| **Total** | **~1h 18 min** |

Comfortably **under the 2-hour Path B budget**. Skip the optional
"Update Home page to read from API" step from the task brief — it's
explicitly described as "minimal change" but the customer-facing
HomeFAQ.tsx is already rendering the hardcoded data correctly, and
swapping it for a network read adds runtime risk for zero visible
benefit. The SEO-side wiring (which is what Phase 4.5d cares about)
works regardless of what the customer page reads from.

Phase 6 / future polish can flip HomeFAQ.tsx to consume `/api/v1/faqs`
when there's a reason to (e.g. operator-edited FAQ updates need to
go live without a frontend redeploy).

## 4. Filament `hint()` API

`vendor/filament/forms/src/Components/Concerns/HasHint.php:34`:

```php
public function hint(string | Htmlable | Closure | null $hint): static
public function hintColor(string | array | Closure | null $color): static
```

Both `hint()` and `hintColor()` accept Closures. Combined with
`->live(debounce: 250)` on the same TextInput, the closure is
re-evaluated on every input change. **No JS asset needed.**
Feature 5a (char counter) is a one-liner with the Closure-form
hint.

## 5. Backend test baseline

```
Tests: 137 passed (622 assertions)
Duration: 37.06s
```

Confirmed from `SITEMAP_FIX_REPORT.md` close-out. Phase 4.5d
target: 137 + ~13 = ~150 passing.

## 6. No file changes from audit

Audit is read-only. Path B decided; PART B starts the diff.

— Audit complete · Path B chosen (minimal FAQ infrastructure, no HomeFAQ.tsx changes)
