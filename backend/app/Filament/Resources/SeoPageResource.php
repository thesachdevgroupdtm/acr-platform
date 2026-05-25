<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\SeoFieldGroup;
use App\Filament\Resources\SeoPageResource\Pages;
use App\Models\SeoPage;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Phase 4.5b — Filament admin resource for SeoPage.
 *
 * First consumer of the reusable SeoFieldGroup (Phase 4.5a). The
 * 20 SEO fields are persisted to the polymorphic seo_metadata
 * table via the page-class hooks (saveSeoData / mutateFormDataBeforeFill),
 * NOT inline on seo_pages.
 */
class SeoPageResource extends Resource
{
    protected static ?string $model = SeoPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 60;

    protected static ?string $navigationLabel = 'SEO Pages';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Page Content')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set, string $operation) {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(200)
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->rules([
                                static fn (): Closure => static function (string $attribute, $value, Closure $fail) {
                                    if (in_array($value, SeoPage::reservedSlugs(), true)) {
                                        $fail("The slug '{$value}' is reserved by a system route. Choose another.");
                                    }
                                },
                            ])
                            ->helperText('URL path. Cannot be a reserved system path (cart, services, admin, payment, etc.).'),
                        Forms\Components\Textarea::make('excerpt')
                            ->maxLength(300)
                            ->rows(2)
                            ->helperText('Short description shown on /explore listing cards.'),
                        Forms\Components\RichEditor::make('body')
                            ->required()
                            ->toolbarButtons([
                                'bold', 'italic', 'h2', 'h3',
                                'bulletList', 'orderedList',
                                'link', 'blockquote',
                            ])
                            ->helperText('Page content. HTML auto-sanitized on save (whitelist: p, h2, h3, h4, strong, em, ul, ol, li, a, blockquote, br, img).')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Categorization')
                    ->schema([
                        Forms\Components\TextInput::make('category')
                            ->maxLength(100)
                            ->datalist([
                                'Service Guide',
                                'Brand Service',
                                'City Service',
                                'Maintenance Tips',
                                'News',
                            ])
                            ->helperText('Drives the /explore filter dropdown. Pick from the suggestions or type a new one.'),
                        Forms\Components\TagsInput::make('tags')
                            ->placeholder('Add tags…')
                            ->helperText('Free-form tags used for filtering and internal-link suggestions.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Call-to-Action')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('cta_title')
                            ->maxLength(100)
                            ->helperText('e.g. "Book Your Audi Service Today"'),
                        Forms\Components\TextInput::make('cta_button_text')
                            ->maxLength(50)
                            ->placeholder('Book Now'),
                        Forms\Components\TextInput::make('cta_button_url')
                            ->placeholder('/cart  or  https://wa.me/...')
                            ->helperText('Internal path or external URL.'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Publishing')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->helperText('Page is visible to customers when on.'),
                        Forms\Components\Toggle::make('is_featured')
                            ->helperText('Surfaces in /explore Hero + Trending sections. Pick 3-5 best.'),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->helperText('Auto-set on first publish; editable for backdating.'),
                    ])
                    ->columns(3),

                // Phase 4.5a SEO field group — first consumer.
                ...SeoFieldGroup::make(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->title),
                Tables\Columns\TextColumn::make('slug')
                    ->copyable()
                    ->fontFamily('mono')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('tags')
                    ->label('# Tags')
                    ->state(fn ($record) => is_array($record->tags) ? count($record->tags) : 0)
                    ->badge()
                    ->color('gray'),
                Tables\Columns\ToggleColumn::make('is_published'),
                Tables\Columns\ToggleColumn::make('is_featured')
                    ->label('Featured'),
                // Phase 4.5d Feature 5b — SEO completeness badge.
                Tables\Columns\IconColumn::make('seo_status')
                    ->label('SEO')
                    ->icon(fn (string $state): string => match ($state) {
                        'complete' => 'heroicon-o-check-circle',
                        'partial'  => 'heroicon-o-exclamation-triangle',
                        default    => 'heroicon-o-minus-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'complete' => 'success',
                        'partial'  => 'warning',
                        default    => 'gray',
                    })
                    ->tooltip(fn (string $state): string => match ($state) {
                        'complete' => 'SEO complete',
                        'partial'  => 'SEO partial — missing title or description',
                        default    => 'No SEO record',
                    }),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not yet'),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
                Tables\Filters\SelectFilter::make('category')
                    ->options(fn () => SeoPage::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all()),
                Tables\Filters\Filter::make('has_seo')
                    ->label('Has SEO record')
                    ->query(fn (Builder $q): Builder => $q->whereHas('seoMetadata')),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Preview')
                    ->icon('heroicon-m-eye')
                    ->url(fn (SeoPage $record) => self::previewUrl($record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Delete this SEO page? Its SEO record is also removed. This cannot be undone.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSeoPages::route('/'),
            'create' => Pages\CreateSeoPage::route('/create'),
            'edit'   => Pages\EditSeoPage::route('/{record}/edit'),
        ];
    }

    /**
     * Phase 4.5b-fix — preview URL builder.
     *
     * Builds the customer-facing URL using `config('app.frontend_url')`
     * so the Preview action opens the React app, not the Filament
     * /admin host. Production sets FRONTEND_URL to the public origin;
     * dev defaults to :3000 (or whichever port Vite picked).
     */
    public static function previewUrl(SeoPage $record): string
    {
        // Two fallback paths so an explicit-null config still gets
        // a sane host. config(key, default) only fires the default
        // when the KEY is missing — once the key exists with value
        // null (e.g. tests setting it to null), the default never
        // kicks in. This guard handles both cases.
        $configured = config('app.frontend_url');
        $base = is_string($configured) && $configured !== ''
            ? rtrim($configured, '/')
            : 'http://localhost:3000';
        return $base . '/' . $record->slug;
    }

    /**
     * SEO field names persisted to the polymorphic seo_metadata
     * table — used by Create/Edit page hooks to slice the form
     * payload into "SeoPage columns" vs "SEO record columns".
     *
     * @return array<int, string>
     */
    public static function seoFieldNames(): array
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
}
