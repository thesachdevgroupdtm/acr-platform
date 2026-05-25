<?php

namespace App\Filament\Resources\CarBrandResource\Pages;

use App\Filament\Concerns\HasMasterDataImportActions;
use App\Filament\Resources\CarBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCarBrands extends ListRecords
{
    use HasMasterDataImportActions;

    protected static string $resource = CarBrandResource::class;

    protected function masterDataKind(): string
    {
        return 'brands';
    }

    protected function getHeaderActions(): array
    {
        return array_merge(
            [Actions\CreateAction::make()],
            // Phase 4.3.1 — wire the Template / Export / Import buttons.
            $this->masterDataHeaderActions(),
        );
    }
}
