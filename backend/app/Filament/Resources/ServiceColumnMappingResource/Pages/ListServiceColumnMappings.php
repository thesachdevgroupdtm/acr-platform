<?php

namespace App\Filament\Resources\ServiceColumnMappingResource\Pages;

use App\Filament\Resources\ServiceColumnMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceColumnMappings extends ListRecords
{
    protected static string $resource = ServiceColumnMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
