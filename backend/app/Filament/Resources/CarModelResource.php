<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CarModelResource\Pages;
use App\Models\CarBrand;
use App\Models\CarModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Phase 4.3.1 — CarModel admin CRUD.
 *
 * Closes the UI gap for the Family A ModelsImport. Slug uniqueness
 * is scoped per-brand (car_models_brand_id_slug_unique compound
 * index) — the form validation respects that scope so the operator
 * can have a 'i20' under both Hyundai and any future re-branding.
 */
class CarModelResource extends Resource
{
    protected static ?string $model = CarModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 61;

    protected static ?string $navigationGroup = 'Vehicle Catalogue';

    protected static ?string $navigationLabel = 'Models';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Model')
                ->schema([
                    Forms\Components\Select::make('brand_id')
                        ->label('Brand')
                        ->relationship('brand', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live(),
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
                        ->helperText('Auto-fills on create. SEO-sensitive — edit cautiously.')
                        ->rule(function (Get $get, ?CarModel $record) {
                            // Slug uniqueness is scoped per brand
                            // (car_models_brand_id_slug_unique).
                            return Rule::unique('car_models', 'slug')
                                ->where('brand_id', $get('brand_id'))
                                ->ignore($record?->id);
                        }),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('image')
                        ->label('Image')
                        ->image()
                        ->disk('public')
                        ->directory('entity-images/models')
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
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('brand'))
            ->defaultSort('brand.name', 'asc')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->height(40),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
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
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->options(fn () => CarBrand::orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('uploadImage')
                    ->label('Image')
                    ->icon('heroicon-o-photo')
                    ->modalHeading('Upload image')
                    ->modalSubmitActionLabel('Save')
                    ->fillForm(fn (CarModel $record): array => ['image' => $record->image])
                    ->form([
                        Forms\Components\FileUpload::make('image')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('entity-images/models')
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
                                fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, CarModel $record): string =>
                                    $record->slug . '.' . $file->getClientOriginalExtension()
                            )
                            ->required(),
                    ])
                    ->action(fn (array $data, CarModel $record) => $record->update(['image' => $data['image']])),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCarModels::route('/'),
            'create' => Pages\CreateCarModel::route('/create'),
            'edit'   => Pages\EditCarModel::route('/{record}/edit'),
        ];
    }
}
