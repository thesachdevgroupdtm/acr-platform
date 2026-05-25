<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CarBrandResource\Pages;
use App\Models\CarBrand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Phase 4.3.1 — CarBrand admin CRUD.
 *
 * Closes the UI gap from Phase 4.3 deviation 1: the Family A
 * BrandsImport works programmatically (proven by tests), but had
 * no admin surface. This resource gives operators a place to view,
 * edit, and (via HeaderActions on the list page) bulk-import or
 * export the 14 seeded brands plus any new ones.
 *
 * No SEO retrofit, no image upload, no statistics — scope is
 * "minimum CRUD" per D-4.3.1-2.
 */
class CarBrandResource extends Resource
{
    protected static ?string $model = CarBrand::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 60;

    protected static ?string $navigationGroup = 'Vehicle Catalogue';

    protected static ?string $navigationLabel = 'Brands';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Brand')
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
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('image')
                        ->label('Image')
                        ->image()
                        ->disk('public')
                        ->directory('entity-images/brands')
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
                Tables\Columns\TextColumn::make('models_count')
                    ->label('Models')
                    ->counts('models')
                    ->badge()
                    ->color('info'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->fillForm(fn (CarBrand $record): array => ['image' => $record->image])
                    ->form([
                        Forms\Components\FileUpload::make('image')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('entity-images/brands')
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
                                fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file, CarBrand $record): string =>
                                    $record->slug . '.' . $file->getClientOriginalExtension()
                            )
                            ->required(),
                    ])
                    ->action(fn (array $data, CarBrand $record) => $record->update(['image' => $data['image']])),
                Tables\Actions\DeleteAction::make()
                    ->before(function (CarBrand $record, Tables\Actions\DeleteAction $action) {
                        $count = $record->models()->count();
                        if ($count > 0) {
                            Notification::make()
                                ->title("Cannot delete — {$count} car model(s) reference this brand.")
                                ->body('Delete or reassign those models first.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    })
                    ->modalDescription(fn (CarBrand $record) => $record->models()->count() > 0
                        ? 'This brand has ' . $record->models()->count() . ' model(s). Deletion is blocked.'
                        : 'Delete this brand? This action is permanent.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCarBrands::route('/'),
            'create' => Pages\CreateCarBrand::route('/create'),
            'edit'   => Pages\EditCarBrand::route('/{record}/edit'),
        ];
    }
}
