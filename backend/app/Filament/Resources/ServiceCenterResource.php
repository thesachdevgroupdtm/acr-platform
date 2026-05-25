<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\SeoFieldGroup;
use App\Filament\Resources\ServiceCenterResource\Pages;
use App\Models\ServiceCenter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Phase 4.5c — Filament admin resource for ServiceCenter.
 *
 * Deferred from Phase 4.2; finally landing here so admins can
 * manage the 4 seeded centers (Moti Nagar, Gurugram, Noida, Okhla)
 * plus add new ones without a seeder edit.
 *
 * - Form mirrors the verified service_centers schema (12 cols) +
 *   the SEO field group with default schema_type = 'LocalBusiness'.
 * - Slug auto-fills from name on CREATE only (SEO-sacrosanct).
 * - Delete is conditionally allowed: blocked when any order
 *   references this center (orders.service_center_id is the FK).
 */
class ServiceCenterResource extends Resource
{
    protected static ?string $model = ServiceCenter::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Service Centers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Center Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(200)
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-fills from name on create. SEO-sensitive — edit cautiously.'),
                        Forms\Components\TextInput::make('phone')
                            ->required()
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(150),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Address')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('city')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('state')
                            ->required()
                            ->maxLength(100)
                            ->default('Delhi NCR'),
                        Forms\Components\TextInput::make('pincode')
                            ->required()
                            ->maxLength(10),
                        Forms\Components\TextInput::make('latitude')
                            ->numeric()
                            ->step(0.0000001)
                            ->helperText('Decimal degrees, 7-decimal precision.'),
                        Forms\Components\TextInput::make('longitude')
                            ->numeric()
                            ->step(0.0000001),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Visibility')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first.'),
                    ])
                    ->columns(2),

                // Phase 4.5c — service centers are physical locations,
                // so 'LocalBusiness' is the canonical Schema.org type.
                // SchemaTemplateEngine pulls address / phone / openHours
                // from this row when generating JSON-LD.
                ...SeoFieldGroup::make('LocalBusiness'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('phone'),
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
                Tables\Filters\SelectFilter::make('city')
                    ->options(fn () => ServiceCenter::query()
                        ->distinct()
                        ->orderBy('city')
                        ->pluck('city', 'city')
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (ServiceCenter $record, Tables\Actions\DeleteAction $action) {
                        $orderCount = $record->orders()->count();
                        if ($orderCount > 0) {
                            Notification::make()
                                ->title("Cannot delete — {$orderCount} order(s) reference this center.")
                                ->body('Reassign or close those orders first.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    })
                    ->modalDescription(fn (ServiceCenter $record) => $record->orders()->count() > 0
                        ? 'This center has ' . $record->orders()->count() . ' order(s). Deletion is blocked.'
                        : 'Delete this center? This action is permanent.'),
            ])
            // Phase 4.5d Feature 5c — bulk SEO generation.
            // Centers default to schema_type='LocalBusiness' (real-
            // world physical location) — matches the SeoFieldGroup
            // default for this resource.
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
                                    'Visit ACR Mechanics %s — trusted multi-brand car service in Delhi NCR. Skilled technicians, transparent pricing, factory-grade equipment.',
                                    $record->name
                                ),
                                'schema_type'        => 'LocalBusiness',
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
            'index'  => Pages\ListServiceCenters::route('/'),
            'create' => Pages\CreateServiceCenter::route('/create'),
            'edit'   => Pages\EditServiceCenter::route('/{record}/edit'),
        ];
    }
}
