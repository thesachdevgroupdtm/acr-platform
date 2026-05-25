<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Phase 4.2 — Filament admin resource for Coupon.
 *
 * - Code is auto-uppercased on save (D-4.2-11) via dehydrateStateUsing.
 * - T&C is the existing `description` column rendered as RichEditor
 *   (D-4.2-7). The DB schema has no separate `terms` column, so
 *   `description` does double duty as both basic description and T&C
 *   (audit §5).
 * - No bulk actions (D-4.2-6).
 */
class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Code')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(40)
                            ->unique(ignoreRecord: true)
                            ->placeholder('FIRST10')
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state === null
                                ? null
                                : strtoupper($state))
                            ->extraInputAttributes(['style' => 'text-transform: uppercase']),
                        Forms\Components\TextInput::make('name')
                            ->label('Title')
                            ->required()
                            ->maxLength(120),
                        Forms\Components\TextInput::make('badge')
                            ->maxLength(20)
                            ->helperText('Optional short flag — e.g. NEW, POPULAR'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Discount')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->options([
                                'percent' => 'Percent (%)',
                                'flat'    => 'Flat (₹)',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('discount_value')
                            ->label(fn (Forms\Get $get) => $get('discount_type') === 'flat'
                                ? '₹ off'
                                : 'Percent off (%)')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('min_order_value')
                            ->label('Minimum cart total')
                            ->prefix('₹')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('max_discount')
                            ->label('Cap for percent discount')
                            ->prefix('₹')
                            ->numeric()
                            ->visible(fn (Forms\Get $get) => $get('discount_type') === 'percent'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Validity')
                    ->schema([
                        Forms\Components\DatePicker::make('expiry_date')
                            ->helperText('Leave blank for never expires'),
                        Forms\Components\TextInput::make('usage_limit')
                            ->label('Total uses (all customers)')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave blank for unlimited'),
                        Forms\Components\TextInput::make('usage_per_user')
                            ->label('Uses per customer')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave blank for unlimited'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_featured')
                            ->helperText('Show on homepage strip'),
                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Terms & Conditions')
                    ->description('Markdown-rich legal text shown on the coupon detail page.')
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->label('Terms & Description')
                            ->required()
                            ->toolbarButtons([
                                'bold', 'italic', 'bulletList',
                                'orderedList', 'link', 'blockquote',
                                'h2', 'h3',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Title')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->name),
                Tables\Columns\TextColumn::make('discount_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percent' => 'success',
                        'flat'    => 'info',
                        default   => 'gray',
                    }),
                Tables\Columns\TextColumn::make('discount_value')
                    ->label('Value')
                    ->formatStateUsing(fn ($state, $record) => $record->discount_type === 'percent'
                        ? rtrim(rtrim((string) $state, '0'), '.') . '%'
                        : '₹' . number_format((float) $state, 0)),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expires')
                    ->date()
                    ->placeholder('Never')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('usages_count')
                    ->label('Used')
                    ->counts('usages')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\SelectFilter::make('discount_type')
                    ->options([
                        'percent' => 'Percent',
                        'flat'    => 'Flat',
                    ]),
                Tables\Filters\Filter::make('currently_valid')
                    ->label('Currently valid')
                    ->query(fn (Builder $q): Builder => $q
                        ->where('is_active', true)
                        ->where(fn (Builder $sub) => $sub
                            ->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>', now()))),
                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $q): Builder => $q
                        ->whereNotNull('expiry_date')
                        ->where('expiry_date', '<', now())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription(fn (Coupon $record) => 'This coupon has '
                        . $record->usages()->count()
                        . ' usage(s) recorded. Deletion is permanent.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit'   => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
