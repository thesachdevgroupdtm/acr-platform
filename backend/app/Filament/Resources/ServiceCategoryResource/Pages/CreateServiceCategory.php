<?php

namespace App\Filament\Resources\ServiceCategoryResource\Pages;

use App\Filament\Concerns\HandlesSeoFormPersistence;
use App\Filament\Resources\ServiceCategoryResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Phase 4.5c — afterCreate hook persists the SEO field group via
 * the shared trait.
 */
class CreateServiceCategory extends CreateRecord
{
    use HandlesSeoFormPersistence;

    protected static string $resource = ServiceCategoryResource::class;

    protected function afterCreate(): void
    {
        $this->saveSeoFromForm();
    }
}
