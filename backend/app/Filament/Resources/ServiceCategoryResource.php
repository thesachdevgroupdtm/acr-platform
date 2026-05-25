<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\SeoFieldGroup;
use App\Filament\Resources\ServiceCategoryResource\Pages;
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
 * Phase 4.2 — Filament admin resource for ServiceCategory.
 *
 * - Reorderable on `position` column (D-4.2-8). Note actual
 *   column is `position`, not `display_order` (audit §6).
 * - Slug auto-fills from name on CREATE only; on EDIT slug is
 *   left untouched (SEO sacrosanct memory).
 * - Delete is conditionally allowed only when no services
 *   reference this category.
 */
class ServiceCategoryResource extends Resource
{
    protected static ?string $model = ServiceCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Service Categories';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, ?ServiceCategory $record, string $operation) {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(120)
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->helperText('Auto-fills from name on create. Edit cautiously — affects URLs and SEO.'),
                Forms\Components\Textarea::make('description')
                    ->maxLength(500)
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('position')
                    ->label('Display Order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Auto-set when reordering in the list'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),

                Forms\Components\FileUpload::make('image')
                    ->label('Image')
                    ->image()
                    ->disk('public')
                    ->directory('entity-images/categories')
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

                // Service-pages redesign Phase 1 (D-P1-4) — category icon,
                // editable alongside the hero image. Distinct filename
                // ({slug}-icon) so it never overwrites the main image.
                Forms\Components\FileUpload::make('icon_image')
                    ->label('Icon image')
                    ->helperText('Small category icon (used in nav / chips). Separate from the hero image.')
                    ->image()
                    ->disk('public')
                    ->directory('entity-images/categories')
                    ->maxSize(5120)
                    ->imagePreviewHeight('150')
                    ->fetchFileInformation(false)
                    ->visibility('public')
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                    ->previewable(true)
                    ->downloadable()
                    ->openable()
                    ->deletable(true)
                    ->getUploadedFileNameForStorageUsing(
                        fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, \Filament\Forms\Get $get): string =>
                            (($get('slug') ?: \Illuminate\Support\Str::slug((string) $get('name'))) ?: 'image')
                                . '-icon.' . $file->getClientOriginalExtension()
                    )
                    ->columnSpanFull(),

                // Phase 4.5c — SEO retrofit. Category pages don't
                // map cleanly to a single Schema.org type (they're
                // listing pages), so default stays 'None' — admin
                // can pick BreadcrumbList or Article per category.
                ...SeoFieldGroup::make('None'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('position')
            ->defaultSort('position', 'asc')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->height(40),
                Tables\Columns\TextColumn::make('position')
                    ->label('#')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->copyable()
                    ->fontFamily('mono')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('services_count')
                    ->label('Services')
                    ->counts('services')
                    ->badge()
                    ->color('info'),
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
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('uploadImage')
                    ->label('Image')
                    ->icon('heroicon-o-photo')
                    ->modalHeading('Upload image')
                    ->modalSubmitActionLabel('Save')
                    ->fillForm(fn (ServiceCategory $record): array => ['image' => $record->image])
                    ->form([
                        Forms\Components\FileUpload::make('image')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('entity-images/categories')
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
                                fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, ServiceCategory $record): string =>
                                    $record->slug . '.' . $file->getClientOriginalExtension()
                            )
                            ->required(),
                    ])
                    ->action(fn (array $data, ServiceCategory $record) => $record->update(['image' => $data['image']])),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription(function (ServiceCategory $record) {
                        $count = $record->services()->count();
                        if ($count === 0) {
                            return 'Delete this category? This action is permanent.';
                        }
                        return "This category has {$count} service(s). Deleting it will ALSO delete those services and all their prices (cascade). This action is permanent.";
                    }),
            ])
            // Phase 4.5d Feature 5c — bulk SEO generation.
            // Categories default to schema_type='None' (listing pages
            // don't map to a single Schema.org type) — matches the
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
                                'schema_type'        => 'None',
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
            'index'  => Pages\ListServiceCategories::route('/'),
            'create' => Pages\CreateServiceCategory::route('/create'),
            'edit'   => Pages\EditServiceCategory::route('/{record}/edit'),
        ];
    }
}
