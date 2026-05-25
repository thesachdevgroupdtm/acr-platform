<?php

namespace App\Filament\Resources\SeoPageResource\Pages;

use App\Filament\Actions\PreviewSchemaJsonLdAction;
use App\Filament\Concerns\HandlesSeoFormPersistence;
use App\Filament\Resources\SeoPageResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * Phase 4.5b — SeoPage edit handler.
 * Phase 4.5c — SEO persistence lifted to `HandlesSeoFormPersistence`
 * trait. Behaviour identical to the prior inline pattern.
 *
 * On load: hydrate the SEO field group from the polymorphic
 * seo_metadata row.
 *
 * On save: slice the form state and upsert the 20 SEO fields back
 * to the polymorphic row.
 */
class EditSeoPage extends EditRecord
{
    use HandlesSeoFormPersistence;

    protected static string $resource = SeoPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view')
                ->label('Preview')
                ->icon('heroicon-m-eye')
                ->url(fn () => SeoPageResource::previewUrl($this->record))
                ->openUrlInNewTab(),
            // Phase 4.5d — JSON-LD preview modal (reusable action).
            PreviewSchemaJsonLdAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->loadSeoIntoForm($data);
    }

    protected function afterSave(): void
    {
        $this->saveSeoFromForm();
    }

    /**
     * Phase 4.5b-fix — explicit save toast that names the change
     * surface, since SEO edits affect both seo_pages AND
     * seo_metadata in one click.
     */
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('SEO page updated')
            ->body('Changes are live. Sitemap cache busted automatically.');
    }
}
