<?php

namespace App\Filament\Resources\ServiceCenterResource\Pages;

use App\Filament\Actions\PreviewSchemaJsonLdAction;
use App\Filament\Concerns\HandlesSeoFormPersistence;
use App\Filament\Resources\ServiceCenterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceCenter extends EditRecord
{
    use HandlesSeoFormPersistence;

    protected static string $resource = ServiceCenterResource::class;

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
