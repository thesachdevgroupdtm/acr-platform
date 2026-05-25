<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\SeoMetadata;

/**
 * Phase 4.5a — JSON-LD generator.
 *
 * Reads a SeoMetadata row, picks the matching template by
 * `schema_type`, fills the inferable fields from the polymorphic
 * `seoable` parent, and merges any `schema_data` overrides.
 *
 * The Custom path bypasses templates entirely: operator-pasted
 * raw JSON-LD is returned as-is after a json_decode sanity
 * check (invalid JSON returns null — silent skip is preferable
 * to crashing the page render).
 *
 * No new packages — pure PHP arrays + json_encode. See
 * PHASE4_5A_ARCHITECTURE.md §3 for the supported type list.
 */
class SchemaTemplateEngine
{
    /**
     * Generate the JSON-LD string for the given SEO record, or
     * null when the record requests no structured data.
     */
    public function generate(SeoMetadata $seo): ?string
    {
        // Custom override path — operator's raw JSON-LD wins
        // over any template, but we sanity-check it first.
        if ($seo->custom_jsonld) {
            return $this->validateAndReturn($seo->custom_jsonld);
        }

        $type = $seo->schema_type;

        if ($type === 'None' || empty($type)) {
            return null;
        }

        $jsonld = match ($type) {
            'LocalBusiness'   => $this->localBusiness($seo),
            'Service'         => $this->service($seo),
            'FAQPage'         => $this->faqPage($seo),
            'BreadcrumbList'  => $this->breadcrumbList($seo),
            'Article'         => $this->article($seo),
            default           => null,
        };

        if ($jsonld === null) {
            return null;
        }

        // Strip null fields so the rendered JSON-LD doesn't bloat
        // with empty geo/openingHours/etc. arrays.
        $jsonld = $this->compact($jsonld);

        return json_encode(
            $jsonld,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /* ───────────── Templates ───────────── */

    protected function localBusiness(SeoMetadata $seo): ?array
    {
        $center = $seo->seoable;
        if (!$center) {
            return null;
        }

        $extra = $seo->schema_data ?? [];

        return [
            '@context' => 'https://schema.org',
            '@type'    => 'LocalBusiness',
            'name'     => $extra['name']  ?? $center->name ?? 'ACR',
            'image'    => $extra['image'] ?? null,
            'address'  => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $center->address ?? '',
                'addressLocality' => $center->city    ?? '',
                'addressRegion'   => $center->state   ?? '',
                'postalCode'      => $center->pincode ?? '',
                'addressCountry'  => 'IN',
            ],
            'geo' => $center->latitude ? [
                '@type'     => 'GeoCoordinates',
                // Cast through float so the rendered JSON-LD has
                // numeric latitude/longitude rather than the
                // decimal-cast strings ("28.6000000") Eloquent
                // returns from a decimal column.
                'latitude'  => (float) $center->latitude,
                'longitude' => (float) $center->longitude,
            ] : null,
            'telephone'    => $center->phone ?? '',
            'priceRange'   => $extra['priceRange']   ?? '₹₹',
            'openingHours' => $extra['openingHours'] ?? null,
        ];
    }

    protected function service(SeoMetadata $seo): ?array
    {
        $service = $seo->seoable;
        if (!$service) {
            return null;
        }

        $extra = $seo->schema_data ?? [];

        return [
            '@context'    => 'https://schema.org',
            '@type'       => 'Service',
            'name'        => $service->name ?? '',
            'description' => $service->description ?? '',
            'provider'    => [
                '@type' => 'Organization',
                'name'  => 'ACR Mechanics',
            ],
            'offers' => $service->base_price ? [
                '@type'         => 'Offer',
                'price'         => (float) $service->base_price,
                'priceCurrency' => 'INR',
            ] : null,
            'areaServed' => $extra['areaServed'] ?? 'Delhi NCR',
        ];
    }

    protected function faqPage(SeoMetadata $seo): ?array
    {
        // Phase 4.5d — preferred data source is the operator-managed
        // `faqs` table (Path B from PHASE4_5D_AUDIT.md). Active rows,
        // ordered by sort_order then id, are emitted as Question /
        // acceptedAnswer entities.
        //
        // Backwards-compat: if the operator pasted an inline faqs
        // array into schema_data (the Phase 4.5a behaviour), that
        // override still wins so per-page custom FAQs are possible.
        $inline = $seo->schema_data['faqs'] ?? [];
        if (! empty($inline)) {
            $entities = collect($inline)->map(fn ($faq) => [
                '@type'          => 'Question',
                'name'           => $faq['question'] ?? '',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $faq['answer'] ?? '',
                ],
            ])->toArray();
        } else {
            $rows = Faq::query()->active()->ordered()->get();
            if ($rows->isEmpty()) {
                return null;
            }
            $entities = $rows->map(fn (Faq $f) => [
                '@type'          => 'Question',
                'name'           => $f->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $f->answer,
                ],
            ])->toArray();
        }

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    protected function breadcrumbList(SeoMetadata $seo): ?array
    {
        $items = $seo->schema_data['items'] ?? [];
        if (empty($items)) {
            return null;
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => collect($items)
                ->values()
                ->map(fn ($item, $idx) => [
                    '@type'    => 'ListItem',
                    'position' => $idx + 1,
                    'name'     => $item['name'] ?? '',
                    'item'     => $item['url']  ?? '',
                ])
                ->toArray(),
        ];
    }

    protected function article(SeoMetadata $seo): ?array
    {
        $page = $seo->seoable; // SeoPage in Phase 4.5b
        if (!$page) {
            return null;
        }

        return [
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => $page->title ?? $page->name ?? '',
            'description'   => $seo->meta_description ?? '',
            'image'         => $seo->og_image ?? null,
            'datePublished' => $page->created_at?->toIso8601String(),
            'dateModified'  => $page->updated_at?->toIso8601String(),
            'author'        => [
                '@type' => 'Organization',
                'name'  => 'ACR Mechanics',
            ],
        ];
    }

    /* ───────────── Helpers ───────────── */

    /**
     * Verify the custom JSON-LD is parseable JSON before returning
     * it to the page render. Invalid JSON is silently skipped —
     * crashing the render path because an operator pasted broken
     * JSON would be worse than a missing schema.
     */
    protected function validateAndReturn(string $custom): ?string
    {
        json_decode($custom, true);
        return json_last_error() === JSON_ERROR_NONE ? $custom : null;
    }

    /**
     * Recursively drop null values so the rendered JSON-LD stays
     * compact (no `"image": null, "openingHours": null,` noise).
     */
    protected function compact(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            $out[$key] = is_array($value) ? $this->compact($value) : $value;
        }
        return $out;
    }
}
