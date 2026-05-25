<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\CarBrand;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Phase 4.5.3 — Filament admin resource for Lead.
 *
 * Audit-trail policy (D-4.5.3-6):
 *   - Customer-submitted fields (name, email, phone, brand, model,
 *     service) are READ-ONLY. Operators only edit `status` + `notes`.
 *   - NO delete action. Funnel reporting requires retention.
 *   - Three quick-action buttons in the table for the common status
 *     transitions: contacted, converted, spam.
 */
class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Submission')
                    ->description('Customer-submitted — read-only audit trail')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('phone')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('email')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('brand.name')
                            ->label('Brand')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('model.name')
                            ->label('Model')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('service.name')
                            ->label('Service')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('source')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP address')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('created_at')
                            ->label('Submitted at')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Operator notes')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'new'       => 'New',
                                'contacted' => 'Contacted',
                                'converted' => 'Converted',
                                'spam'      => 'Spam',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(2000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->copyable()
                    ->searchable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('model.name')
                    ->label('Model')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new'       => 'warning',
                        'contacted' => 'info',
                        'converted' => 'success',
                        'spam'      => 'danger',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('source')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable()
                    ->tooltip(fn ($record) => $record->created_at?->format('Y-m-d H:i')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'new'       => 'New',
                        'contacted' => 'Contacted',
                        'converted' => 'Converted',
                        'spam'      => 'Spam',
                    ]),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->options(fn () => CarBrand::orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('source')
                    ->options(fn () => Lead::query()
                        ->select('source')
                        ->distinct()
                        ->orderBy('source')
                        ->pluck('source', 'source')
                        ->toArray()),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(fn (Builder $q, array $data): Builder => $q
                        ->when($data['from'] ?? null, fn ($qq, $d) => $qq->whereDate('created_at', '>=', $d))
                        ->when($data['to']   ?? null, fn ($qq, $d) => $qq->whereDate('created_at', '<=', $d))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markContacted')
                    ->label('Mark Contacted')
                    ->icon('heroicon-o-phone-arrow-up-right')
                    ->color('info')
                    ->visible(fn (Lead $record) => $record->status === 'new')
                    ->requiresConfirmation()
                    ->action(fn (Lead $record) => $record->update(['status' => 'contacted'])),
                Tables\Actions\Action::make('markConverted')
                    ->label('Mark Converted')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Lead $record) => in_array($record->status, ['new', 'contacted'], true))
                    ->requiresConfirmation()
                    ->action(fn (Lead $record) => $record->update(['status' => 'converted'])),
                Tables\Actions\Action::make('markSpam')
                    ->label('Mark Spam')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Lead $record) => $record->status !== 'spam')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as spam?')
                    ->modalDescription('Spam leads stay in the table for audit but get filtered out of funnel reports.')
                    ->action(fn (Lead $record) => $record->update(['status' => 'spam'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'view'  => Pages\ViewLead::route('/{record}'),
            'edit'  => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
