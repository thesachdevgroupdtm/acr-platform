<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Concerns\HasMasterDataImportActions;
use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServices extends ListRecords
{
    use HasMasterDataImportActions;

    protected static string $resource = ServiceResource::class;

    protected function masterDataKind(): string
    {
        return 'services';
    }

    protected function getHeaderActions(): array
    {
        return array_merge(
            [Actions\CreateAction::make()],
            // Phase 4.3 — Template / Export / Import for services bulk ops.
            $this->masterDataHeaderActions(),
        );
    }
}
