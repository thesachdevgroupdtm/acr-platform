<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Concerns\HandlesSeoFormPersistence;
use App\Filament\Resources\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Phase 4.5c — afterCreate hook persists the SEO field group via
 * the shared trait. Saves the Service row first (lifecycle order
 * matters — afterCreate runs after $this->record is set).
 */
class CreateService extends CreateRecord
{
    use HandlesSeoFormPersistence;

    protected static string $resource = ServiceResource::class;

    protected function afterCreate(): void
    {
        $this->saveSeoFromForm();
    }
}
