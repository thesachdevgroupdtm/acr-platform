<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceColumnMappingResource\Pages;
use App\Models\Service;
use App\Models\ServiceColumnMapping;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Phase 4.3 — admin for the saved-alias layer of the pricing
 * matrix's 4-layer column resolution (D-4.3-2 Layer 2).
 *
 * Operator uses this page to:
 *   1. Pre-create mappings before the first import (saves
 *      mapping decisions on day 1).
 *   2. Mark a column as "always ignore" (service_id=null,
 *      is_active=true).
 *   3. Audit mappings the import flow auto-saved from previous
 *      preview-confirm cycles.
 */
class ServiceColumnMappingResource extends Resource
{
    protected static ?string $model = ServiceColumnMapping::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?int $navigationSort = 95;

    protected static ?string $navigationGroup = 'Data Operations';

    protected static ?string $navigationLabel = 'Service mappings';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('excel_column')
                ->required()
                ->maxLength(200)
                ->helperText('The header text as it appears in the Excel file. Case-insensitive at lookup time.'),
            Forms\Components\Select::make('service_id')
                ->label('Maps to service')
                ->options(fn () => Service::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->placeholder('Ignore this column')
                ->helperText('Leave blank to flag the column as "always ignore" during imports.'),
            Forms\Components\Toggle::make('is_active')
                ->default(true)
                ->helperText('Inactive mappings are bypassed at import time.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('excel_column', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('excel_column')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->placeholder('— ignored —')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                Tables\Columns\ToggleColumn::make('is_active'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Added by')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\Filter::make('unmapped')
                    ->label('Marked "ignore"')
                    ->query(fn ($q) => $q->whereNull('service_id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServiceColumnMappings::route('/'),
            'create' => Pages\CreateServiceColumnMapping::route('/create'),
            'edit'   => Pages\EditServiceColumnMapping::route('/{record}/edit'),
        ];
    }
}
