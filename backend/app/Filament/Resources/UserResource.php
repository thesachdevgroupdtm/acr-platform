<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Phase 4.2 — Filament admin resource for User.
 *
 * Security boundaries (D-4.2-4, D-4.2-5, D-4.2-10):
 *   - Password field is INTENTIONALLY ABSENT from the form.
 *     Admin must not set/reset passwords via Filament; the OTP
 *     flow handles all credentialing.
 *   - Phone is read-only when is_verified_phone === true.
 *   - "Toggle Admin" action cannot fire on the acting admin's
 *     own row (self-protection).
 *
 * No delete action: orders.user_id is restrictOnDelete.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(15)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn ($record) => $record?->is_verified_phone === true)
                            ->helperText(fn ($record) => $record?->is_verified_phone === true
                                ? 'Phone is verified — contact support to change.'
                                : null),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_verified_phone')
                            ->label('Phone Verified')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Read-only — set by OTP flow'),
                        Forms\Components\Toggle::make('is_verified_email')
                            ->label('Email Verified')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Read-only'),
                        Forms\Components\Toggle::make('is_admin')
                            ->label('Admin')
                            ->helperText('Grants /admin panel access'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Meta')
                    ->schema([
                        Forms\Components\TextInput::make('created_at')
                            ->label('Registered')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('updated_at')
                            ->label('Last Updated')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('last_login_at')
                            ->label('Last Login')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->since()
                    ->sortable()
                    ->tooltip(fn ($record) => $record->created_at?->format('Y-m-d H:i')),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('Admin'),
                Tables\Filters\TernaryFilter::make('is_verified_phone')
                    ->label('Phone Verified'),
                Tables\Filters\Filter::make('has_orders')
                    ->label('Has Orders')
                    ->query(fn (Builder $q): Builder => $q->whereHas('orders')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleAdmin')
                    ->label(fn (User $record) => $record->is_admin
                        ? 'Revoke Admin'
                        : 'Make Admin')
                    ->icon('heroicon-o-shield-check')
                    ->color(fn (User $record) => $record->is_admin ? 'danger' : 'warning')
                    ->disabled(fn (User $record) => $record->id === auth()->id())
                    ->tooltip(fn (User $record) => $record->id === auth()->id()
                        ? 'Cannot toggle your own admin status.'
                        : null)
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => $record->is_admin
                        ? "Remove admin access from {$record->name}?"
                        : "Make {$record->name} an admin?")
                    ->action(function (User $record): void {
                        if ($record->id === auth()->id()) {
                            return;
                        }
                        $record->is_admin = ! $record->is_admin;
                        $record->save();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
