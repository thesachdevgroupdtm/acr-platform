<?php

namespace App\Filament\Resources\ServiceCategoryResource\Pages;

use App\Filament\Actions\PreviewSchemaJsonLdAction;
use App\Filament\Concerns\HandlesSeoFormPersistence;
use App\Filament\Resources\ServiceCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Phase 4.5c — Edit page wires the SEO trait.
 */
class EditServiceCategory extends EditRecord
{
    use HandlesSeoFormPersistence;

    protected static string $resource = ServiceCategoryResource::class;

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
