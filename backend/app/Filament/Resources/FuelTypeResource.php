<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FuelTypeResource\Pages;
use App\Models\FuelType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Phase 4.3.1 — FuelType admin CRUD.
 *
 * Closes the UI gap for the Family A FuelTypesImport. Delete is
 * blocked when service_prices rows reference the row (FuelType
 * has a prices() HasMany that drives the check).
 */
class FuelTypeResource extends Resource
{
    protected static ?string $model = FuelType::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?int $navigationSort = 62;

    protected static ?string $navigationGroup = 'Vehicle Catalogue';

    protected static ?string $navigationLabel = 'Fuel types';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Fuel Type')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
                            if ($operation === 'create' && filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        }),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(100)
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->helperText('Auto-fills on create. SEO-sensitive — edit cautiously.'),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('image')
                        ->label('Image')
                        ->image()
                        ->disk('public')
                        ->directory('entity-images/fuel-types')
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
            ->defaultSort('name', 'asc')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->height(40),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->copyable()
                    ->fontFamily('mono')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('prices_count')
                    ->label('Priced rows')
                    ->counts('prices')
                    ->badge()
                    ->color('info'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
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
                    ->fillForm(fn (FuelType $record): array => ['image' => $record->image])
                    ->form([
                        Forms\Components\FileUpload::make('image')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('entity-images/fuel-types')
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
                                fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, FuelType $record): string =>
                                    $record->slug . '.' . $file->getClientOriginalExtension()
                            )
                            ->required(),
                    ])
                    ->action(fn (array $data, FuelType $record) => $record->update(['image' => $data['image']])),
                Tables\Actions\DeleteAction::make()
                    ->before(function (FuelType $record, Tables\Actions\DeleteAction $action) {
                        $count = $record->prices()->count();
                        if ($count > 0) {
                            Notification::make()
                                ->title("Cannot delete — {$count} service_price row(s) reference this fuel type.")
                                ->body('Remove or reassign those prices first.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    })
                    ->modalDescription(fn (FuelType $record) => $record->prices()->count() > 0
                        ? 'This fuel type has ' . $record->prices()->count() . ' price row(s). Deletion is blocked.'
                        : 'Delete this fuel type? This action is permanent.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFuelTypes::route('/'),
            'create' => Pages\CreateFuelType::route('/create'),
            'edit'   => Pages\EditFuelType::route('/{record}/edit'),
        ];
    }
}
