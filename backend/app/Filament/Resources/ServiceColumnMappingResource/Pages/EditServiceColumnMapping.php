<?php

namespace App\Filament\Resources\ServiceColumnMappingResource\Pages;

use App\Filament\Resources\ServiceColumnMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceColumnMapping extends EditRecord
{
    protected static string $resource = ServiceColumnMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
