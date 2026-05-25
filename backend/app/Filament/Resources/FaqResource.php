<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Phase 4.5d — admin CRUD for the FAQ table.
 *
 * Powers two surfaces:
 *   - The FAQPage Schema.org template (rendered by
 *     SchemaTemplateEngine when a SeoMetadata row has
 *     schema_type=FAQPage).
 *   - The public GET /api/v1/faqs endpoint.
 *
 * Simple resource — no SEO retrofit on this resource itself; FAQs
 * are *data* for SEO, not SEO-bearing themselves.
 */
class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?int $navigationSort = 80;

    protected static ?string $navigationLabel = 'FAQs';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('question')
                ->required()
                ->maxLength(500)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('answer')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0)
                ->helperText('Lower numbers appear first. Reorder via drag-handle on the list.'),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('question')
                    ->searchable()
                    ->limit(80)
                    ->tooltip(fn (Faq $r) => $r->question),
                Tables\Columns\TextColumn::make('answer')
                    ->limit(120)
                    ->color('gray')
                    ->tooltip(fn (Faq $r) => $r->answer),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit'   => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
