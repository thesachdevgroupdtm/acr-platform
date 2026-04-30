<?php

namespace App\Helpers;

/**
 * SeoHelper — converts existing meta_* / canonical / image fields on a model
 * (Page, ServiceCategory, ScheduledPackage, Product, Seo, etc.) into a
 * normalised payload the React frontend can hand to <SeoHead>.
 *
 * No DB changes — derives Open Graph + JSON-LD from fields that already exist.
 */
class SeoHelper
{
    /**
     * Build a unified SEO payload.
     *
     * @param  array{
     *   title?: ?string,
     *   description?: ?string,
     *   keywords?: ?string,
     *   canonical?: ?string,
     *   image?: ?string,
     *   url?: ?string,
     *   type?: ?string,
     *   schema?: ?string,
     *   extra_meta?: ?string,
     *   site_name?: ?string,
     * }  $input
     */
    public static function build(array $input): array
    {
        $title       = $input['title']       ?? null;
        $description = $input['description'] ?? null;
        $canonical   = $input['canonical']   ?? ($input['url'] ?? null);
        $image       = $input['image']       ?? null;
        $url         = $input['url']         ?? $canonical;
        $type        = $input['type']        ?? 'website';
        $siteName    = $input['site_name']   ?? config('app.name', 'ACR');

        return [
            'title'       => $title,
            'description' => $description,
            'keywords'    => $input['keywords'] ?? null,
            'canonical'   => $canonical,
            'extra_meta'  => $input['extra_meta'] ?? null,
            'og' => [
                'title'       => $title,
                'description' => $description,
                'type'        => $type,
                'url'         => $url,
                'image'       => $image,
                'site_name'   => $siteName,
            ],
            'twitter' => [
                'card'        => 'summary_large_image',
                'title'       => $title,
                'description' => $description,
                'image'       => $image,
            ],
            'json_ld' => $input['schema'] ?? self::defaultSchema($type, $title, $description, $url, $image, $siteName),
        ];
    }

    /**
     * Convenience: extract SEO fields from a model row that uses ACR's
     * common naming convention (meta_title / meta_description / meta_keyword
     * or meta_keywords / canonical_tag / extra_meta_tag or extra_meta_description).
     */
    public static function fromModel($model, array $overrides = []): array
    {
        if (!$model) {
            return self::build($overrides);
        }

        return self::build(array_merge([
            'title'       => $model->meta_title       ?? null,
            'description' => $model->meta_description ?? null,
            'keywords'    => $model->meta_keyword     ?? ($model->meta_keywords ?? null),
            'canonical'   => $model->canonical_tag    ?? null,
            'extra_meta'  => $model->extra_meta_tag   ?? ($model->extra_meta_description ?? null),
            'image'       => isset($model->image) ? self::asset('uploads/' . ltrim((string) $model->image, '/')) : null,
        ], $overrides));
    }

    protected static function defaultSchema(?string $type, ?string $title, ?string $description, ?string $url, ?string $image, ?string $siteName): array
    {
        $context = '@context';
        $typeKey = '@type';
        $payload = [
            $context => 'https://schema.org',
            $typeKey => $type === 'product' ? 'Product' : ($type === 'article' ? 'Article' : 'WebPage'),
            'name'        => $title,
            'description' => $description,
        ];
        if ($url)   $payload['url']   = $url;
        if ($image) $payload['image'] = $image;
        if ($siteName) {
            $payload['publisher'] = [
                $typeKey => 'Organization',
                'name'   => $siteName,
            ];
        }
        return $payload;
    }

    protected static function asset(string $path): string
    {
        if (preg_match('~^https?://~i', $path)) return $path;
        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}
