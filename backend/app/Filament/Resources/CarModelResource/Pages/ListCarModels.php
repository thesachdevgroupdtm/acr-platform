<?php

namespace App\Filament\Resources\CarModelResource\Pages;

use App\Filament\Concerns\HasMasterDataImportActions;
use App\Filament\Resources\CarModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCarModels extends ListRecords
{
    use HasMasterDataImportActions;

    protected static string $resource = CarModelResource::class;

    protected function masterDataKind(): string
    {
        return 'models';
    }

    protected function getHeaderActions(): array
    {
        return array_merge(
            [Actions\CreateAction::make()],
            $this->masterDataHeaderActions(),
        );
    }
}
