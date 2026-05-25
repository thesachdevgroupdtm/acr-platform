<?php

namespace App\Filament\Resources\SeoPageResource\Pages;

use App\Filament\Concerns\HandlesSeoFormPersistence;
use App\Filament\Resources\SeoPageResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

/**
 * Phase 4.5b — SeoPage create handler.
 * Phase 4.5c — SEO persistence lifted to `HandlesSeoFormPersistence`
 * trait so all four SEO-bearing resources stay in lock-step.
 *
 * Two responsibilities:
 *   1. Stamp created_by from the authenticated admin so the
 *      Edit table can show authorship.
 *   2. After the SeoPage row is created, persist the 20 SEO fields
 *      to the polymorphic seo_metadata table via the trait.
 */
class CreateSeoPage extends CreateRecord
{
    use HandlesSeoFormPersistence;

    protected static string $resource = SeoPageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->saveSeoFromForm();
    }

    /**
     * Phase 4.5b-fix — explicit success toast. Filament's default
     * notification reads "Created" with the record-class noun.
     * Override to a customer-facing string so the operator's
     * mental model lines up with the customer-visible page.
     */
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('SEO page created')
            ->body('The page is saved. Toggle "is_published" if you\'re ready for customers to see it.');
    }
}
