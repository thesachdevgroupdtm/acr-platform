<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;

/**
 * Phase 4.5a — reusable Filament form group for the 20 SEO fields.
 * Phase 4.5c — `make()` now accepts an optional default schema type
 * so each resource can hint the most-likely Schema.org type for its
 * records (e.g. 'Service' for ServiceResource, 'LocalBusiness' for
 * ServiceCenterResource). Backwards compatible: no-arg call still
 * resolves to 'None'.
 *
 * Drop into any resource via:
 *
 *     ->schema([
 *         // … resource fields …
 *         ...SeoFieldGroup::make('Service'),
 *     ]);
 *
 * Returns a single collapsed Section containing 5 tabs (Basic,
 * Open Graph, Twitter, Schema.org, Advanced).
 *
 * Helper text is operator-friendly (no jargon, examples included).
 * See PHASE4_5A_ARCHITECTURE.md §4 for the full design.
 */
class SeoFieldGroup
{
    /**
     * @param  string  $defaultSchemaType  Default value for the
     *         Schema.org `schema_type` Select. One of: None,
     *         LocalBusiness, Service, FAQPage, BreadcrumbList,
     *         Article, Custom.
     * @return array<int, Section>
     */
    public static function make(string $defaultSchemaType = 'None'): array
    {
        return [
            Section::make('SEO Settings')
                ->description('Manage how this page appears in search engines, social media, and structured data.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Tabs::make('SEO Tabs')
                        ->tabs([
                            self::basicTab(),
                            self::openGraphTab(),
                            self::twitterTab(),
                            self::schemaTab($defaultSchemaType),
                            self::advancedTab(),
                        ]),
                ]),
        ];
    }

    /* ───────────── Tabs ───────────── */

    protected static function basicTab(): Tab
    {
        // Phase 4.5d Feature 5a — live char counters with traffic-light
        // colour bands. `->live(debounce: 250)` triggers re-evaluation
        // of the hint Closure on each keystroke (250 ms throttle).
        $titleLen = fn (?string $state): int => mb_strlen($state ?? '');
        $titleHintColor = fn (?string $state): string => match (true) {
            $titleLen($state) === 0      => 'gray',
            $titleLen($state) < 30       => 'warning',
            $titleLen($state) <= 60      => 'success',
            default                      => 'danger',
        };
        $descLen = fn (?string $state): int => mb_strlen($state ?? '');
        $descHintColor = fn (?string $state): string => match (true) {
            $descLen($state) === 0      => 'gray',
            $descLen($state) < 120      => 'warning',
            $descLen($state) <= 160     => 'success',
            default                     => 'danger',
        };

        return Tab::make('Basic SEO')
            ->icon('heroicon-m-magnifying-glass')
            ->schema([
                TextInput::make('meta_title')
                    ->maxLength(70)
                    ->live(debounce: 250)
                    ->hint(fn (?string $state) => $titleLen($state) . ' / 60 chars')
                    ->hintColor($titleHintColor)
                    ->helperText('Appears in browser tab and search results. Max 70 chars.')
                    ->placeholder('e.g. Audi Service in Delhi | ACR'),
                Textarea::make('meta_description')
                    ->maxLength(160)
                    ->rows(2)
                    ->live(debounce: 250)
                    ->hint(fn (?string $state) => $descLen($state) . ' / 160 chars')
                    ->hintColor($descHintColor)
                    ->helperText('Search-result snippet. Max 160 chars for best display.')
                    ->placeholder('Quality Audi service and repair in Delhi NCR…'),
                TextInput::make('meta_keywords')
                    ->maxLength(255)
                    ->helperText('Comma-separated. Modern SEO uses these less, but harmless to include.'),
                TextInput::make('canonical_url')
                    ->url()
                    ->helperText('Leave blank to use the current page URL. Override only for duplicate content.'),
                Select::make('robots_meta')
                    ->options([
                        'index,follow'     => 'Index + Follow (default — searchable)',
                        'noindex,follow'   => 'No Index + Follow (hidden from search, links followed)',
                        'index,nofollow'   => 'Index + No Follow (searchable, links not followed)',
                        'noindex,nofollow' => 'No Index + No Follow (hidden completely)',
                    ])
                    ->default('index,follow'),
            ]);
    }

    protected static function openGraphTab(): Tab
    {
        return Tab::make('Open Graph')
            ->icon('heroicon-m-share')
            ->schema([
                TextInput::make('og_title')
                    ->maxLength(70)
                    ->helperText('Title when shared on Facebook, WhatsApp, LinkedIn. Defaults to meta_title.'),
                Textarea::make('og_description')
                    ->maxLength(200)
                    ->rows(2)
                    ->helperText('Description when shared. Defaults to meta_description.'),
                TextInput::make('og_image')
                    ->url()
                    ->helperText('Image when shared. Recommended: 1200×630px JPG/PNG.'),
                TextInput::make('og_keywords')
                    ->maxLength(255),
                Select::make('og_type')
                    ->options([
                        'website'           => 'Website (default)',
                        'article'           => 'Article',
                        'business.business' => 'Business',
                        'product'           => 'Product',
                    ])
                    ->default('website'),
            ]);
    }

    protected static function twitterTab(): Tab
    {
        return Tab::make('Twitter Cards')
            ->icon('heroicon-m-chat-bubble-left')
            ->schema([
                Select::make('twitter_card')
                    ->options([
                        'summary'             => 'Summary (small image)',
                        'summary_large_image' => 'Summary with large image (recommended)',
                    ])
                    ->default('summary_large_image'),
                TextInput::make('twitter_title')
                    ->maxLength(70)
                    ->helperText('Defaults to og_title or meta_title.'),
                Textarea::make('twitter_description')
                    ->maxLength(200)
                    ->rows(2),
                TextInput::make('twitter_image')
                    ->url()
                    ->helperText('Defaults to og_image. Recommended: 1200×675px.'),
            ]);
    }

    protected static function schemaTab(string $defaultSchemaType = 'None'): Tab
    {
        return Tab::make('Schema.org')
            ->icon('heroicon-m-code-bracket')
            ->schema([
                Select::make('schema_type')
                    ->options([
                        'None'           => 'None (skip structured data)',
                        'LocalBusiness'  => 'LocalBusiness (for service centers)',
                        'Service'        => 'Service (for individual services)',
                        'FAQPage'        => 'FAQ Page',
                        'BreadcrumbList' => 'Breadcrumb List',
                        'Article'        => 'Article (for SEO content pages)',
                        'Custom'         => 'Custom (use Custom JSON-LD field)',
                    ])
                    ->default($defaultSchemaType)
                    ->live()
                    ->helperText('Choose the structured-data type. Templates auto-fill from this resource.'),
                KeyValue::make('schema_data')
                    ->keyLabel('Field')
                    ->valueLabel('Value')
                    ->helperText('Optional template overrides or extras. e.g. priceRange, openingHours.')
                    ->reorderable()
                    ->visible(fn (Get $get) => ! in_array(
                        $get('schema_type'),
                        ['None', 'Custom'],
                        true
                    )),
            ]);
    }

    protected static function advancedTab(): Tab
    {
        return Tab::make('Advanced')
            ->icon('heroicon-m-cog-6-tooth')
            ->schema([
                Textarea::make('custom_jsonld')
                    ->rows(10)
                    ->helperText('Paste raw JSON-LD here for full control. Overrides Schema tab. Validate with Google Rich Results Test before saving.')
                    ->placeholder('{ "@context": "https://schema.org", … }'),
                Toggle::make('include_in_sitemap')
                    ->default(true)
                    ->helperText('Uncheck to exclude this page from sitemap.xml.'),
                Select::make('priority')
                    ->options([
                        '0.1' => '0.1 (Lowest)',
                        '0.3' => '0.3',
                        '0.5' => '0.5 (Default)',
                        '0.7' => '0.7',
                        '0.9' => '0.9',
                        '1.0' => '1.0 (Highest)',
                    ])
                    ->default('0.5')
                    ->helperText('Sitemap priority hint to search engines (0.0 to 1.0).'),
                Select::make('changefreq')
                    ->options([
                        'always'  => 'Always',
                        'hourly'  => 'Hourly',
                        'daily'   => 'Daily',
                        'weekly'  => 'Weekly',
                        'monthly' => 'Monthly (default)',
                        'yearly'  => 'Yearly',
                        'never'   => 'Never',
                    ])
                    ->default('monthly')
                    ->helperText('How often this page changes. Sitemap hint.'),
            ]);
    }
}
