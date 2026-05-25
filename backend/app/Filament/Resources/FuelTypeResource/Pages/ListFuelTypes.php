<?php

namespace App\Filament\Resources\FuelTypeResource\Pages;

use App\Filament\Concerns\HasMasterDataImportActions;
use App\Filament\Resources\FuelTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFuelTypes extends ListRecords
{
    use HasMasterDataImportActions;

    protected static string $resource = FuelTypeResource::class;

    protected function masterDataKind(): string
    {
        return 'fuel_types';
    }

    protected function getHeaderActions(): array
    {
        return array_merge(
            [Actions\CreateAction::make()],
            $this->masterDataHeaderActions(),
        );
    }
}
