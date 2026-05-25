<?php

namespace App\Filament\Concerns;

use App\Exports\MasterDataExport;
use App\Imports\BrandsImport;
use App\Imports\FuelTypesImport;
use App\Imports\ModelsImport;
use App\Imports\ServicesImport;
use App\Models\Import;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Phase 4.3 — adds Download Template / Export / Import header
 * actions to a Family A master data List page.
 *
 *   class ListServices extends ListRecords {
 *       use HasMasterDataImportActions;
 *
 *       protected function masterDataKind(): string { return 'services'; }
 *
 *       protected function getHeaderActions(): array {
 *           return $this->masterDataHeaderActions();
 *       }
 *   }
 *
 * Sub-class supplies `masterDataKind()` returning one of:
 *   brands | models | fuel_types | services
 */
trait HasMasterDataImportActions
{
    /** Sub-class must implement. */
    abstract protected function masterDataKind(): string;

    /** @return array<int, \Filament\Actions\Action> */
    protected function masterDataHeaderActions(): array
    {
        $kind = $this->masterDataKind();

        return [
            Actions\Action::make('downloadTemplate')
                ->label('Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => (new MasterDataExport($kind, templateOnly: true))->download("{$kind}-template.xlsx")),

            Actions\Action::make('exportData')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => (new MasterDataExport($kind))->download("{$kind}.xlsx")),

            Actions\Action::make('importData')
                ->label('Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('upload')
                        ->label('Excel file (.xlsx)')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->required(),
                ])
                ->action(function (array $data) use ($kind) {
                    $relPath = $data['upload'];
                    $absolute = Storage::disk('local')->path($relPath);

                    $import = $this->buildImporter($kind);

                    $audit = Import::create([
                        'user_id'     => auth()->id(),
                        'import_type' => $kind,
                        'file_name'   => basename($relPath),
                        'file_size'   => @filesize($absolute) ?: 0,
                        'file_path'   => $relPath,
                        'status'      => Import::STATUS_COMMITTING,
                    ]);

                    DB::transaction(function () use ($import, $absolute) {
                        Excel::import($import, $absolute);
                    });

                    $audit->update([
                        'status'        => Import::STATUS_COMPLETED,
                        'rows_total'    => $import->rowsTotal,
                        'rows_valid'    => $import->rowsValid,
                        'rows_invalid'  => $import->rowsInvalid,
                        'rows_skipped'  => $import->rowsSkipped,
                        'error_summary' => $import->errorLog,
                        'committed_at'  => now(),
                    ]);

                    Notification::make()
                        ->success()
                        ->title("Imported {$import->rowsValid} {$kind} record(s)")
                        ->body($import->rowsInvalid > 0
                            ? "Skipped {$import->rowsInvalid} invalid row(s). Check Import history for details."
                            : 'All rows imported successfully.')
                        ->send();
                }),
        ];
    }

    protected function buildImporter(string $kind): \App\Imports\BaseImport
    {
        return match ($kind) {
            'brands'     => new BrandsImport(),
            'models'     => new ModelsImport(),
            'fuel_types' => new FuelTypesImport(),
            'services'   => new ServicesImport(),
            default      => throw new \InvalidArgumentException("Unknown master kind: {$kind}"),
        };
    }
}
