<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    /**
     * No DeleteAction in the header — funnel reporting requires
     * lead retention. Status `spam` is the soft-archive option.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
