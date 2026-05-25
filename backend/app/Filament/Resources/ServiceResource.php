<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\SeoFieldGroup;
use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use App\Models\ServiceCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Phase 4.2 — Filament admin resource for Service.
 *
 * - Belongs to ServiceCategory via category_id (audit §7).
 * - duration is split into time_takes (numeric) + time_unit
 *   (string) in the schema; the form exposes both fields.
 * - Slug auto-fills on create. Slug uniqueness is per category
 *   (DB unique on category_id+slug).
 * - Delete blocked when service_prices rows exist for this
 *   service.
 */
class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basics')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(150)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(150)
                            ->alphaDash()
                            ->helperText('Auto-fills on create. SEO-sensitive — edit cautiously.'),
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(fn () => ServiceCategory::orderBy('position')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\FileUpload::make('image')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('entity-images/services')
                            ->maxSize(5120)
                            ->imagePreviewHeight('150')
                            ->fetchFileInformation(false)
                            ->visibility('public')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->previewable(true)
                            ->downloadable()
                            ->openable()
                            ->deletable(true)
                            ->getUploadedFileNameForStorageUsing(
                                fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, \Filament\Forms\Get $get): string =>
                                    (($get('slug') ?: \Illuminate\Support\Str::slug((string) $get('name'))) ?: 'image')
                                        . '.' . $file->getClientOriginalExtension()
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing & Duration')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->prefix('₹')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Base price; vehicle-specific overrides come from pricing rows in Phase 4.3.'),
                        Forms\Components\TextInput::make('time_takes')
                            ->label('Duration')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\Select::make('time_unit')
                            ->options([
                                'minutes' => 'minutes',
                                'hours'   => 'hours',
                            ])
                            ->default('minutes'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Content')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('warrenty_info')
                            ->label('Warranty info')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('recommended_info')
                            ->label('Recommended info')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('interval_info')
                            ->label('Service interval')
                            ->maxLength(255)
                            ->helperText('Display copy, e.g. "Every 5000 km or 3 months".')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('note')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Service-pages redesign Phase 1 (D-P1-1) — "what's
                // included" line items, persisted to service_inclusions
                // via the hasMany relationship. `orderColumn('position')`
                // auto-maintains row order on drag-reorder + save.
                Forms\Components\Section::make("What's Included")
                    ->description('Bulleted line items shown on the service page (e.g. "Engine Oil Replacement"). Drag to reorder.')
                    ->schema([
                        Forms\Components\Repeater::make('inclusions')
                            ->relationship()
                            ->hiddenLabel()
                            ->schema([
                                // Phase 1.5 (D-1.5-1/2) — GoMechanic-style bucket.
                                // Nullable; "Ungrouped" until set (operator or the
                                // inclusions:autogroup classifier assigns it).
                                Forms\Components\Select::make('group_name')
                                    ->label('Group')
                                    ->options([
                                        'Essential'   => 'Essential',
                                        'Performance' => 'Performance',
                                        'Additional'  => 'Additional',
                                    ])
                                    ->placeholder('Ungrouped')
                                    ->default(null)
                                    ->native(false)
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('label')
                                    ->required()
                                    ->maxLength(150)
                                    ->columnSpan(2),
                                Forms\Components\FileUpload::make('image')
                                    ->label('Thumbnail (optional)')
                                    ->image()
                                    ->disk('public')
                                    ->directory('entity-images/service-inclusions')
                                    ->maxSize(5120)
                                    ->imagePreviewHeight('80')
                                    ->fetchFileInformation(false)
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                                    ->previewable(true)
                                    ->downloadable()
                                    ->openable()
                                    ->deletable(true)
                                    ->getUploadedFileNameForStorageUsing(
                                        fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, \Filament\Forms\Get $get): string =>
                                            (\Illuminate\Support\Str::slug((string) $get('label')) ?: 'inclusion')
                                                . '-' . substr(md5(uniqid('', true)), 0, 8)
                                                . '.' . $file->getClientOriginalExtension()
                                    )
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->orderColumn('position')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => empty($state['label'])
                                ? null
                                : $state['label'] . (empty($state['group_name']) ? '' : "  ·  {$state['group_name']}"))
                            ->addActionLabel('Add inclusion')
                            ->defaultItems(0),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Phase 4.5c — SEO retrofit. 'Service' is the brand-
                // canonical Schema.org type for an individual service
                // page; SchemaTemplateEngine auto-fills from this row.
                ...SeoFieldGroup::make('Service'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->height(40),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_price')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_takes')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? $state . ' ' . ($record->time_unit ?: 'min')
                        : '—'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => ServiceCategory::orderBy('position')->pluck('name', 'id')),
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\Filter::make('has_duration')
                    ->label('Has duration')
                    ->query(fn ($q) => $q->whereNotNull('time_takes')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('uploadImage')
                    ->label('Image')
                    ->icon('heroicon-o-photo')
                    ->modalHeading('Upload image')
                    ->modalSubmitActionLabel('Save')
                    ->fillForm(fn (Service $record): array => ['image' => $record->image])
                    ->form([
                        Forms\Components\FileUpload::make('image')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('entity-images/services')
                            ->maxSize(5120)
                            ->imagePreviewHeight('150')
                            ->fetchFileInformation(false)
                            ->visibility('public')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->previewable(true)
                            ->downloadable()
                            ->openable()
                            ->deletable(true)
                            ->getUploadedFileNameForStorageUsing(
                                fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, Service $record): string =>
                                    $record->slug . '.' . $file->getClientOriginalExtension()
                            )
                            ->required(),
                    ])
                    ->action(fn (array $data, Service $record) => $record->update(['image' => $data['image']])),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Service $record, Tables\Actions\DeleteAction $action) {
                        $priceCount = $record->prices()->count();
                        if ($priceCount > 0) {
                            Notification::make()
                                ->title("Cannot delete — {$priceCount} pricing row(s) reference this service.")
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    })
                    ->modalDescription(fn (Service $record) => $record->prices()->count() > 0
                        ? 'This service has ' . $record->prices()->count() . ' pricing row(s). Deletion is blocked.'
                        : 'Delete this service? This action is permanent.'),
            ])
            // Phase 4.5d Feature 5c — bulk SEO generation. Skips
            // records that already have a seo_metadata row so a
            // second click can't blow away existing operator-edited
            // SEO. Schema type defaults to 'Service' to match the
            // SeoFieldGroup default for this resource.
            ->bulkActions([
                Tables\Actions\BulkAction::make('generateBasicSeo')
                    ->label('Generate basic SEO')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->seoMetadata) {
                                continue;
                            }
                            $record->setSeoData([
                                'meta_title'         => $record->name . ' | ACR Mechanics',
                                'meta_description'   => sprintf(
                                    'Professional %s service at ACR Mechanics. Trusted multi-brand workshop in Delhi NCR with skilled technicians and transparent pricing.',
                                    $record->name
                                ),
                                'schema_type'        => 'Service',
                                'include_in_sitemap' => true,
                                'priority'           => 0.6,
                                'changefreq'         => 'weekly',
                            ]);
                            $count++;
                        }
                        Notification::make()
                            ->success()
                            ->title("Generated SEO for {$count} record(s)")
                            ->body($count === 0
                                ? 'All selected records already had SEO. Nothing changed.'
                                : 'Records without SEO got a starter title + description. Existing SEO was untouched.')
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit'   => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
