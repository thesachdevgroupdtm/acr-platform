<?php

namespace App\Filament\Resources\ServiceCenterResource\Pages;

use App\Filament\Resources\ServiceCenterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceCenters extends ListRecords
{
    protected static string $resource = ServiceCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
