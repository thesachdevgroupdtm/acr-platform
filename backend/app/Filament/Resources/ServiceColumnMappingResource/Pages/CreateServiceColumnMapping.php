<?php

namespace App\Filament\Resources\ServiceColumnMappingResource\Pages;

use App\Filament\Resources\ServiceColumnMappingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceColumnMapping extends CreateRecord
{
    protected static string $resource = ServiceColumnMappingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
