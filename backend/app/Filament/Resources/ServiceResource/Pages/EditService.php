<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Actions\PreviewSchemaJsonLdAction;
use App\Filament\Concerns\HandlesSeoFormPersistence;
use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Phase 4.5c — Edit page wires the SEO trait. Hydrate from
 * seoMetadata on load; upsert back after save.
 */
class EditService extends EditRecord
{
    use HandlesSeoFormPersistence;

    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Phase 4.5d — JSON-LD preview modal.
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
}
