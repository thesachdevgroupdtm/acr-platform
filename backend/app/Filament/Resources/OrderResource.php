<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\ServiceCenter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Phase 4.2 — Filament admin resource for Order.
 *
 * Operational read/edit + status-aware action set per
 * /PHASE4_2 spec D-4.2-9. The Order model's transitionTo()
 * enforces pending → confirmed → in_service → completed; this
 * resource exposes a simpler 3-action set (Confirm / Cancel /
 * Mark Completed) that bypasses the in_service intermediate.
 * That deviation is documented in PHASE4_2_AUDIT.md §10.
 *
 * No bulk actions (D-4.2-6). No delete (data integrity).
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer')
                    ->description('Read-only — frozen at order placement')
                    ->schema([
                        Forms\Components\TextInput::make('name_snapshot')
                            ->label('Name')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('phone_snapshot')
                            ->label('Phone')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('email_snapshot')
                            ->label('Email')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Booking')
                    ->schema([
                        Forms\Components\Select::make('service_center_id')
                            ->label('Service Center')
                            ->options(fn () => ServiceCenter::query()
                                ->orderBy('sort_order')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending'    => 'Pending',
                                'confirmed'  => 'Confirmed',
                                'in_service' => 'In Service',
                                'completed'  => 'Completed',
                                'cancelled'  => 'Cancelled',
                            ])
                            ->required()
                            ->disabled(fn ($record) => in_array(
                                $record?->status,
                                ['completed', 'cancelled'],
                                true
                            )),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending'  => 'Pending',
                                'paid'     => 'Paid',
                                'failed'   => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('preferred_date')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('preferred_time')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Snapshots')
                    ->description('Frozen at booking time; read-only')
                    ->schema([
                        Forms\Components\Placeholder::make('items_display')
                            ->label('Services')
                            ->content(fn ($record) => static::renderItems($record))
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('vehicle_display')
                            ->label('Vehicle')
                            ->content(fn ($record) => static::renderVehicle($record)),
                        Forms\Components\Placeholder::make('address_display')
                            ->label('Address')
                            ->content(fn ($record) => $record?->address ?: '—'),
                        Forms\Components\TextInput::make('total')
                            ->prefix('₹')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Cancellation')
                    ->visible(fn ($record) => $record?->status === 'cancelled')
                    ->schema([
                        Forms\Components\TextInput::make('cancelled_reason')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->disabled()
                            ->dehydrated(false),
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
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_snapshot')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('serviceCenter.name')
                    ->label('Center')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'    => 'warning',
                        'confirmed'  => 'info',
                        'in_service' => 'info',
                        'completed'  => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->money('INR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed')
                    ->since()
                    ->sortable()
                    ->tooltip(fn ($record) => $record->created_at?->format('Y-m-d H:i')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'pending'    => 'Pending',
                        'confirmed'  => 'Confirmed',
                        'in_service' => 'In Service',
                        'completed'  => 'Completed',
                        'cancelled'  => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('service_center_id')
                    ->label('Service Center')
                    ->options(fn () => ServiceCenter::pluck('name', 'id')),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(fn (Builder $q, array $data): Builder => $q
                        ->when($data['from'] ?? null, fn ($qq, $d) => $qq->whereDate('created_at', '>=', $d))
                        ->when($data['to']   ?? null, fn ($qq, $d) => $qq->whereDate('created_at', '<=', $d))),
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $q): Builder => $q->whereDate('created_at', today())),
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $q): Builder => $q->whereBetween('created_at', [
                        now()->startOfWeek(), now()->endOfWeek(),
                    ])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm this booking?')
                    ->modalDescription('Customer should be notified manually if no notification system is configured.')
                    ->action(function (Order $record): void {
                        $record->status       = 'confirmed';
                        $record->confirmed_at = now();
                        $record->save();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record) => in_array($record->status, ['pending', 'confirmed'], true))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Cancellation reason')
                            ->required()
                            ->minLength(10)
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->status           = 'cancelled';
                        $record->cancelled_at     = now();
                        $record->cancelled_reason = $data['reason'];
                        $record->save();
                    }),
                Tables\Actions\Action::make('markCompleted')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Order $record) => $record->status === 'confirmed')
                    ->requiresConfirmation()
                    ->modalHeading('Mark this order completed?')
                    ->action(function (Order $record): void {
                        $record->status       = 'completed';
                        $record->completed_at = now();
                        $record->save();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'view'   => Pages\ViewOrder::route('/{record}'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    /**
     * Render order_items as a readable list (not raw JSON).
     */
    protected static function renderItems(?Order $record): string
    {
        if (!$record) {
            return '—';
        }
        $items = $record->items()->get();
        if ($items->isEmpty()) {
            return '—';
        }
        return $items->map(function ($it) {
            $title = $it->service_title_snapshot ?: ('Service #' . $it->service_id);
            $price = '₹' . number_format((float) $it->line_total_snapshot, 2);
            $qty   = (int) $it->quantity;
            return "{$title} × {$qty} — {$price}";
        })->implode("\n");
    }

    /**
     * Render vehicle_snapshot JSON as "Brand Model (Fuel)".
     */
    protected static function renderVehicle(?Order $record): string
    {
        if (!$record || !is_array($record->vehicle_snapshot)) {
            return '—';
        }
        $v     = $record->vehicle_snapshot;
        $brand = $v['brand_name'] ?? $v['brand'] ?? '';
        $model = $v['model_name'] ?? $v['model'] ?? '';
        $fuel  = $v['fuel_name']  ?? $v['fuel']  ?? '';
        $line  = trim("{$brand} {$model}");
        if ($fuel !== '') {
            $line .= " ({$fuel})";
        }
        return $line === '' ? '—' : $line;
    }
}
