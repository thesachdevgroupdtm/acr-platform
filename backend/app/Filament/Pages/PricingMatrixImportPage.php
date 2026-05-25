<?php

namespace App\Filament\Pages;

use App\Exceptions\AutoBootstrapException;
use App\Exports\PricingMatrixExport;
use App\Filament\Pages\Exceptions\InvalidUploadStateException;
use App\Models\Import;
use App\Services\Imports\AutoBootstrapResolver;
use App\Services\Imports\PricingMatrixImporter;
use App\Services\Imports\PricingMatrixPreviewService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Phase 4.3.2 — operator-friendly redesign of the pricing-matrix
 * import flow.
 *
 * Replaces the Phase 4.3 two-state ($preview === null vs non-null)
 * with an explicit 5-state Livewire state machine:
 *
 *   upload      — first render; file picker visible
 *   analyzing   — short transition state while the preview service
 *                 reads the file
 *   preview     — operator reviews + adjusts column matches
 *   importing   — short transition state while the importer runs
 *   success     — import done; either fully successful or partial
 *
 * Plain-English vocabulary throughout — no "alias", "fuzzy",
 * "unmapped", "exact", "NA". Status labels mapped in
 * `confidenceLabel()` so the Blade view never has to translate.
 *
 * Operator can:
 *   - Override any column's matched service via dropdown.
 *   - Toggle "Save these matches" to persist overrides into the
 *     `service_column_mappings` table for the next upload.
 *
 * Backend touch in this phase: a single new
 * `bool $persistMappings = true` parameter on
 * `PricingMatrixImporter::commit()`. Default preserves prior
 * behaviour; the UI toggle wires it through.
 */
class PricingMatrixImportPage extends Page implements HasForms
{
    use InteractsWithForms;

    public const STATE_UPLOAD    = 'upload';
    public const STATE_ANALYZING = 'analyzing';
    public const STATE_PREVIEW   = 'preview';
    public const STATE_IMPORTING = 'importing';
    public const STATE_SUCCESS   = 'success';

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationGroup = 'Data Operations';

    protected static ?int $navigationSort = 85;

    protected static ?string $navigationLabel = 'Pricing matrix import';

    protected static string $view = 'filament.pages.pricing-matrix-import';

    /** @var array<string, mixed> */
    public ?array $uploadData = [];

    /** @var array<string, mixed>|null  preview payload from PreviewService */
    public ?array $preview = null;

    public ?string $uploadedPath = null;

    public ?int $auditImportId = null;

    public string $state = self::STATE_UPLOAD;

    /**
     * Operator-supplied column → service_id overrides. Keyed by the
     * raw Excel header text (the Blade dropdowns use the same key).
     *
     * @var array<string, int|null>
     */
    public array $columnMappingOverrides = [];

    public bool $saveMappings = true;

    /**
     * Phase 4.3.5 — AutoBootstrapResolver report rendered as a summary
     * card in STATE_PREVIEW. Null until analyze() has run. Shape per
     * D-4.3.5-7: per-entity matched_existing / auto_created / list of
     * created_entities, plus a column_mappings rollup.
     *
     * @var array<string, mixed>|null
     */
    public ?array $bootstrap = null;

    /** @var array<string, int|string>|null  importer result snapshot */
    public ?array $result = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        // Phase 4.3.3 root-cause fix.
        //
        // Previous code combined `FileUpload::make('uploadData.file')`
        // with `->statePath('uploadData')`. Filament treats component
        // names as relative to statePath, so the FileUpload was
        // actually writing to `$this->uploadData['uploadData']['file']`
        // (double prefix) — analyze() could never see the uploaded
        // file. Operator's "click Analyze → nothing happens" symptom
        // comes from analyze()'s `! $rel` early-exit branch hitting
        // a null that should have been the file path.
        //
        // Fix: name the component just `'file'`. Combined with the
        // existing statePath, it correctly resolves to
        // `$this->uploadData['file']`, which is what analyze() reads.
        return $form
            ->schema([
                FileUpload::make('file')
                    ->label('Pricing matrix Excel (.xlsx)')
                    ->disk('local')
                    ->directory('imports')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                    ])
                    ->required(),
            ])
            ->statePath('uploadData');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCurrentMatrix')
                ->label('Download current prices')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => (new PricingMatrixExport())->download('pricing-matrix-' . date('Y-m-d') . '.xlsx')),
        ];
    }

    /**
     * Plain-English label for a resolver confidence value. The
     * Blade view never needs to know the internal vocabulary.
     */
    public function confidenceLabel(?string $confidence): string
    {
        return match ($confidence) {
            'exact'    => 'Matched',
            'alias'    => 'Saved match',
            'fuzzy'    => 'Likely match',
            'ignored'  => 'Skipped',
            default    => 'Needs attention',
        };
    }

    public function confidenceColor(?string $confidence): string
    {
        return match ($confidence) {
            'exact'    => 'success',
            'alias'    => 'info',
            'fuzzy'    => 'warning',
            'ignored'  => 'gray',
            default    => 'danger',
        };
    }

    // ─── State transitions ─────────────────────────────────────────

    public function analyze(): void
    {
        Log::info('Phase4.3.3: analyze() invoked');

        try {
            $absolute = $this->resolveUploadedFilePath();
        } catch (InvalidUploadStateException $e) {
            Log::error('Phase4.3.4: analyze() file resolution failed', [
                'message' => $e->getMessage(),
            ]);
            $this->state = self::STATE_UPLOAD;
            Notification::make()
                ->danger()
                ->title('Upload not ready')
                ->body($e->getMessage())
                ->persistent()
                ->send();
            return;
        }

        try {
            $this->state = self::STATE_ANALYZING;

            Log::info('Phase4.3.3: analyze() absolute path resolved', [
                'absolute' => $absolute,
                'exists'   => is_file($absolute),
            ]);

            // Phase 4.3.5 — dry-run bootstrap BEFORE PreviewService.
            // Read-only: walks the file, queries the DB, reports what
            // WOULD be created on commit. Zero DB writes guaranteed
            // by AutoBootstrapResolver::resolveDryRun(). Operator sees
            // the resulting counts in the bootstrap summary card and
            // can cancel without any side-effect.
            Log::info('Phase4.3.5: calling AutoBootstrapResolver::resolveDryRun');
            $resolver = app(AutoBootstrapResolver::class);
            $report   = $resolver->resolveDryRun($absolute);
            $this->bootstrap = $report->toArray();
            Log::info('Phase4.3.5: dry-run completed', [
                'new_brands'     => $report->brands->wouldCreate,
                'new_models'     => $report->models->wouldCreate,
                'new_fuels'      => $report->fuelTypes->wouldCreate,
                'new_services'   => $report->services->wouldCreate,
                'new_categories' => $report->categories->wouldCreate,
            ]);

            Log::info('Phase4.3.3: calling PreviewService::analyze');
            $service = app(PricingMatrixPreviewService::class);
            $this->preview = $service->analyze($absolute);

            Log::info('Phase4.3.3: PreviewService returned', [
                'columns_count' => count($this->preview['column_mappings'] ?? []),
                'rows_total'    => $this->preview['row_summary']['total'] ?? 0,
                'valid_prices'  => $this->preview['price_summary']['valid_prices'] ?? 0,
            ]);

            $this->uploadedPath = $absolute;

            // Seed the override map so the Blade dropdowns start with the
            // resolver's choice; operator can change any.
            $this->columnMappingOverrides = [];
            foreach (($this->preview['column_mappings'] ?? []) as $m) {
                $this->columnMappingOverrides[$m['excel']] = $m['service_id'];
            }

            Log::info('Phase4.3.3: creating Import record');
            $audit = Import::create([
                'user_id'      => auth()->id(),
                'import_type'  => Import::TYPE_PRICING_MATRIX,
                'file_name'    => basename($absolute),
                'file_size'    => @filesize($absolute) ?: 0,
                'file_path'    => $absolute,
                'status'       => Import::STATUS_PREVIEW_READY,
                'rows_total'   => $this->preview['row_summary']['total'] ?? 0,
                'rows_valid'   => $this->preview['row_summary']['valid_vehicles'] ?? 0,
                'rows_invalid' => $this->preview['row_summary']['invalid_vehicles'] ?? 0,
                'rows_skipped' => 0,
                'error_summary' => $this->preview['row_summary']['errors'] ?? [],
            ]);
            $this->auditImportId = $audit->id;
            Log::info('Phase4.3.3: Import created', [
                'id'     => $audit->id,
                'status' => $audit->status,
            ]);

            $this->state = self::STATE_PREVIEW;

            Notification::make()
                ->success()
                ->title('File analyzed')
                ->body(sprintf(
                    'Found %d vehicles and %d services. Review the matches and click Import when ready.',
                    $this->preview['row_summary']['valid_vehicles'] ?? 0,
                    count($this->preview['column_mappings'] ?? []),
                ))
                ->send();
        } catch (\Throwable $e) {
            Log::error('Phase4.3.3: analyze() exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->state = self::STATE_UPLOAD;
            Notification::make()
                ->danger()
                ->title('Analysis failed')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    public function commit(): void
    {
        Log::info('Phase4.3.3: commit() invoked', ['import_id' => $this->auditImportId]);

        try {
            $absolute = $this->resolveUploadedFilePath();
        } catch (InvalidUploadStateException $e) {
            Log::error('Phase4.3.4: commit() file resolution failed', [
                'message' => $e->getMessage(),
            ]);
            Notification::make()
                ->danger()
                ->title('Nothing to import')
                ->body($e->getMessage())
                ->persistent()
                ->send();
            return;
        }

        $bootstrapReport = null;
        $importer        = null;

        try {
            $this->state = self::STATE_IMPORTING;

            // Phase 4.3.5 — wrap the resolver + importer in a single
            // outer transaction so that any failure in either phase
            // rolls back master-data creates AND price upserts together.
            // The importer's internal DB::transaction nests via savepoint.
            DB::transaction(function () use ($absolute, &$bootstrapReport, &$importer) {
                // Step 1 — persist bootstrap.
                Log::info('Phase4.3.5: calling AutoBootstrapResolver::resolveAndPersist', [
                    'import_id' => $this->auditImportId,
                ]);
                $resolver        = app(AutoBootstrapResolver::class);
                $bootstrapReport = $resolver->resolveAndPersist($absolute, $this->auditImportId);
                Log::info('Phase4.3.5: bootstrap persisted', [
                    'created_brands'     => $bootstrapReport->brands->created,
                    'created_models'     => $bootstrapReport->models->created,
                    'created_fuels'      => $bootstrapReport->fuelTypes->created,
                    'created_services'   => $bootstrapReport->services->created,
                    'created_categories' => $bootstrapReport->categories->created,
                ]);

                // Step 2 — run the price importer. PreviewService +
                // Importer constructors preload master-data hashes, so
                // they MUST be instantiated AFTER the bootstrap creates
                // the new rows.
                $previewSvc = app(PricingMatrixPreviewService::class);
                $importer   = new PricingMatrixImporter($previewSvc);

                Log::info('Phase4.3.3: calling Importer::commit', ['absolute' => $absolute]);
                $importer->commit(
                    absolutePath:    $absolute,
                    overrides:       $this->columnMappingOverrides,
                    userId:          auth()->id(),
                    persistMappings: $this->saveMappings,
                );
            });

            $summary = [
                'inserted' => $importer->inserted,
                'updated'  => $importer->updated,
                'skipped'  => $importer->skipped,
                'invalid'  => $importer->invalid,
            ];
            Log::info('Phase4.3.3: Importer::commit completed', $summary);

            if ($this->auditImportId) {
                Import::where('id', $this->auditImportId)->update([
                    'status'        => Import::STATUS_COMPLETED,
                    'rows_skipped'  => $importer->skipped,
                    'committed_at'  => now(),
                ]);
                Log::info('Phase4.3.3: Import audit row updated', [
                    'id'     => $this->auditImportId,
                    'status' => Import::STATUS_COMPLETED,
                ]);
            }

            $this->result = $summary + [
                'totalDone'         => $importer->inserted + $importer->updated,
                'bootstrap_created' => $bootstrapReport?->totalNewEntities() ?? 0,
            ];
            $this->bootstrap = $bootstrapReport?->toArray();

            $this->state = self::STATE_SUCCESS;

            $bootstrapCreated = $bootstrapReport?->totalNewEntities() ?? 0;
            Notification::make()
                ->success()
                ->title('Import complete')
                ->body(sprintf(
                    '%d new + %d updated prices saved.%s',
                    $importer->inserted,
                    $importer->updated,
                    $bootstrapCreated > 0
                        ? sprintf(' Auto-created %d master-data entities.', $bootstrapCreated)
                        : '',
                ))
                ->send();
        } catch (AutoBootstrapException $e) {
            Log::error('Phase4.3.5: bootstrap persistence failed — transaction rolled back', [
                'message' => $e->getMessage(),
                'previous' => $e->getPrevious()?->getMessage(),
            ]);
            $this->state = self::STATE_PREVIEW;
            Notification::make()
                ->danger()
                ->title('Bootstrap failed')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Log::error('Phase4.3.3: commit() exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->state = self::STATE_PREVIEW;
            Notification::make()
                ->danger()
                ->title('Import failed')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    public function cancel(): void
    {
        $this->preview = null;
        $this->uploadedPath = null;
        $this->auditImportId = null;
        $this->columnMappingOverrides = [];
        $this->bootstrap = null;
        $this->result = null;
        $this->form->fill();
        $this->state = self::STATE_UPLOAD;
    }

    public function importAnother(): void
    {
        $this->cancel();
    }

    /**
     * Active service list for the override dropdown. Cached on the
     * Livewire instance so the Blade view doesn't re-query on every
     * re-render.
     *
     * @return array<int, string>  id → name
     */
    public function getServiceOptionsProperty(): array
    {
        return \App\Models\Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Phase 4.3.4 — single source of truth for resolving Filament's
     * FileUpload state to an absolute file path on the local disk.
     *
     * Filament v3's FileUpload writes a hash-keyed array like
     * ['abc123' => 'imports/pmip-xxx.xlsx'] into the form state, which
     * tripped League\Flysystem\PathPrefixer::prefixPath() in Phase 4.3.3
     * (string expected, array given). This helper normalizes ALL
     * documented shapes (see CASES 1-7 in the Phase 4.3.4 task brief)
     * and returns a verified absolute path or throws so the caller can
     * surface a clean operator notification.
     *
     * @throws InvalidUploadStateException When the state is unrecognized
     *         OR the resolved file is not present on disk.
     */
    private function resolveUploadedFilePath(): string
    {
        $file = $this->uploadData['file'] ?? null;

        // CASE 1 + 2 — null or empty array.
        if ($file === null || (is_array($file) && empty($file))) {
            throw InvalidUploadStateException::fromShape(
                $file,
                'No file uploaded — drop a pricing matrix Excel onto the upload area first',
            );
        }

        // CASE 6 — Livewire TemporaryUploadedFile (upload in flight).
        if ($file instanceof TemporaryUploadedFile) {
            $path = $file->getRealPath();
            if (! is_file($path)) {
                throw InvalidUploadStateException::fromShape(
                    $file,
                    'Temporary upload file missing from disk',
                );
            }
            return $path;
        }

        // CASE 3 — direct string path (legacy or manually-set state).
        if (is_string($file)) {
            return $this->resolveStringPath($file);
        }

        // CASE 4 + 5 — Filament v3 default: hash-keyed array.
        if (is_array($file)) {
            if (count($file) > 1) {
                Log::warning('Phase4.3.4: multiple files in upload state, using first', [
                    'count' => count($file),
                    'keys'  => array_keys($file),
                ]);
            }

            $first = reset($file);

            if (is_string($first)) {
                return $this->resolveStringPath($first);
            }

            if ($first instanceof TemporaryUploadedFile) {
                $path = $first->getRealPath();
                if (! is_file($path)) {
                    throw InvalidUploadStateException::fromShape(
                        $first,
                        'Temporary upload (inside array) missing from disk',
                    );
                }
                return $path;
            }

            // CASE 7a — array but inner type is unrecognized.
            throw InvalidUploadStateException::fromShape(
                $file,
                'Array contains unrecognized value type: '
                    . (is_object($first) ? get_class($first) : gettype($first)),
            );
        }

        // CASE 7b — anything else (object that isn't a TUF, resource, etc).
        throw InvalidUploadStateException::fromShape(
            $file,
            'Unrecognized upload state type: '
                . (is_object($file) ? get_class($file) : gettype($file)),
        );
    }

    /**
     * Resolves a string path (absolute or relative-to-local-disk) to a
     * verified absolute path. Throws if the file is not on disk.
     */
    private function resolveStringPath(string $value): string
    {
        // Already an absolute path that exists.
        if (is_file($value)) {
            return $value;
        }

        // Relative to the `local` disk root (storage/app).
        $absolute = Storage::disk('local')->path($value);

        if (! is_file($absolute)) {
            throw InvalidUploadStateException::fromShape(
                $value,
                "File not found at resolved path: {$absolute}. "
                    . "The upload didn't reach storage — check that "
                    . "storage/app/imports/ exists and is writable.",
            );
        }

        return $absolute;
    }
}
