<?php

namespace App\Filament\Concerns;

/**
 * Phase 4.5c — shared SEO form-state ↔ seo_metadata bridge for
 * Filament Create / Edit page classes.
 *
 * Adopted by every resource page that surfaces SeoFieldGroup on its
 * form. Behaviour is identical to Phase 4.5b's inline pattern on
 * `SeoPageResource\Pages\(Create|Edit)SeoPage` — just lifted to a
 * single place so the four resources stay in lock-step.
 *
 *   Create page  →  call $this->saveSeoFromForm() inside afterCreate()
 *   Edit page    →  call $this->loadSeoIntoForm($data) inside
 *                     mutateFormDataBeforeFill($data)
 *                   call $this->saveSeoFromForm() inside afterSave()
 *
 * The model must `use App\Traits\HasSeoMetadata` so `setSeoData()`
 * exists on `$this->record`.
 */
trait HandlesSeoFormPersistence
{
    /**
     * The 20 SEO field names persisted to the polymorphic
     * seo_metadata table. Mirrors `SeoPageResource::seoFieldNames()`
     * and the SeoFieldGroup tabs.
     *
     * @return array<int, string>
     */
    protected function seoFieldNames(): array
    {
        return [
            'meta_title', 'meta_description', 'meta_keywords',
            'canonical_url', 'robots_meta',
            'og_title', 'og_description', 'og_image',
            'og_keywords', 'og_type',
            'twitter_card', 'twitter_title', 'twitter_description',
            'twitter_image',
            'schema_type', 'schema_data', 'custom_jsonld',
            'include_in_sitemap', 'priority', 'changefreq',
        ];
    }

    /**
     * Slice the form payload by `seoFieldNames()`, filter blanks,
     * and upsert via `HasSeoMetadata::setSeoData()`.
     *
     * Null / empty-string values are skipped so the cascade
     * fallback chain isn't shadowed by accidental blanks — same
     * rule Phase 4.5b's inline pattern used.
     */
    protected function saveSeoFromForm(): void
    {
        if (! $this->record) {
            return;
        }

        $state = $this->form->getRawState();
        $names = $this->seoFieldNames();

        $seoData = [];
        foreach ($names as $name) {
            if (! array_key_exists($name, $state)) {
                continue;
            }
            $value = $state[$name];
            if ($value === null || $value === '') {
                continue;
            }
            $seoData[$name] = $value;
        }

        if (! empty($seoData)) {
            $this->record->setSeoData($seoData);
        }
    }

    /**
     * Merge the seo_metadata row into the form data array for the
     * Edit page's `mutateFormDataBeforeFill()` hook. Caller passes
     * the form's $data through and receives an array with the SEO
     * fields populated.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function loadSeoIntoForm(array $data): array
    {
        if (! $this->record) {
            return $data;
        }

        $seo = $this->record->seoMetadata;
        if (! $seo) {
            return $data;
        }

        foreach ($this->seoFieldNames() as $name) {
            $data[$name] = $seo->{$name};
        }

        return $data;
    }
}
