<?php

namespace App\Filament\Resources\ServiceCenterResource\Pages;

use App\Filament\Concerns\HandlesSeoFormPersistence;
use App\Filament\Resources\ServiceCenterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceCenter extends CreateRecord
{
    use HandlesSeoFormPersistence;

    protected static string $resource = ServiceCenterResource::class;

    protected function afterCreate(): void
    {
        $this->saveSeoFromForm();
    }
}
