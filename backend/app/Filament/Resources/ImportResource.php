<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportResource\Pages;
use App\Models\Import;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Phase 4.3 — read-only audit log of every Excel import attempt.
 *
 * Operators see: who uploaded what, the result counts, error
 * summaries. No create/edit form — rows are written by the
 * import controllers.
 */
class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationGroup = 'Data Operations';

    protected static ?string $navigationLabel = 'Import history';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('import_type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        Import::TYPE_PRICING_MATRIX => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('file_name')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->file_name),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        Import::STATUS_COMPLETED  => 'success',
                        Import::STATUS_FAILED     => 'danger',
                        Import::STATUS_COMMITTING => 'warning',
                        default                    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('rows_total')
                    ->label('Total')
                    ->numeric(),
                Tables\Columns\TextColumn::make('rows_valid')
                    ->label('Valid')
                    ->numeric()
                    ->color('success'),
                Tables\Columns\TextColumn::make('rows_invalid')
                    ->label('Invalid')
                    ->numeric()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('rows_skipped')
                    ->label('Skipped')
                    ->numeric()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('By')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('committed_at')
                    ->dateTime()
                    ->placeholder('Not committed')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('import_type')->options([
                    Import::TYPE_BRANDS          => 'Brands',
                    Import::TYPE_MODELS          => 'Models',
                    Import::TYPE_FUEL_TYPES      => 'Fuel Types',
                    Import::TYPE_SERVICES        => 'Services',
                    Import::TYPE_PRICING_MATRIX  => 'Pricing Matrix',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    Import::STATUS_VALIDATING    => 'Validating',
                    Import::STATUS_PREVIEW_READY => 'Preview ready',
                    Import::STATUS_COMMITTING    => 'Committing',
                    Import::STATUS_COMPLETED     => 'Completed',
                    Import::STATUS_FAILED        => 'Failed',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Import details')
                    ->modalContent(fn (Import $record) => view('filament.resources.imports.detail-modal', [
                        'import' => $record,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImports::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;  // imports written by controllers, not manual
    }
}
