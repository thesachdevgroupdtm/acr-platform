<?php

namespace App\Filament\Pages;

use App\Services\Images\BulkImageMatcher;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Bulk image upload — redesign (IMAGE-UPLOAD-FIX PART C).
 *
 * One tab per entity type (Brands / Models / Services / Categories / Fuel
 * types). Each tab has its own upload zone accepting THREE input methods
 * (D-FIX-2): multiple files, a folder (webkitdirectory), or a .zip — all
 * three bind to the tab's Livewire file property.
 *
 * NO analyze→import 2-step (D-FIX-3): selecting files auto-uploads them,
 * the matched ones are stored + linked immediately, and the result
 * ("N uploaded · M not matched: …") is shown. The tab IS the entity type,
 * so filenames match by entity name with no folder prefix (D-FIX-4).
 *
 * Why the redesign (PART A root cause): the previous 2-step page's
 * Import button called commit(), which RE-RESOLVED the uploaded file in a
 * SECOND Livewire round-trip via resolveUploadedFilePath() reading the
 * volatile `uploadData['file']` state, and the button was gated by
 * `:disabled="$report['total_matched'] === 0"`. Across the second
 * round-trip the temporary-upload reference is no longer reliably present,
 * so the re-resolution path no-opped (and a 0-match report disabled the
 * button) — "clicking Import did nothing". Auto-process holds no
 * cross-request upload state and has no separate button, eliminating the
 * failure mode entirely.
 */
class BulkImageUploadPage extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Data Operations';

    protected static ?int $navigationSort = 86;

    protected static ?string $navigationLabel = 'Bulk image upload';

    protected static string $view = 'filament.pages.bulk-image-upload';

    public string $activeTab = 'brands';

    /** Livewire file-upload buckets — one per tab. Each accepts multiple
     *  images, a folder of images, or a .zip. */
    public $brandUploads = [];
    public $modelUploads = [];
    public $serviceUploads = [];
    public $categoryUploads = [];
    public $fuelUploads = [];

    /** Last result per type: type => ImageMatchReport::toArray(). */
    public array $results = [];

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // ── Auto-process hooks: fire the moment Livewire finishes uploading.
    public function updatedBrandUploads(): void    { $this->ingest('brands', $this->brandUploads);       $this->brandUploads = []; }
    public function updatedModelUploads(): void    { $this->ingest('models', $this->modelUploads);       $this->modelUploads = []; }
    public function updatedServiceUploads(): void  { $this->ingest('services', $this->serviceUploads);   $this->serviceUploads = []; }
    public function updatedCategoryUploads(): void { $this->ingest('categories', $this->categoryUploads); $this->categoryUploads = []; }
    public function updatedFuelUploads(): void     { $this->ingest('fuel-types', $this->fuelUploads);     $this->fuelUploads = []; }

    /**
     * Normalize Livewire's uploaded files to the matcher's plain payload
     * and run the per-type auto-process.
     *
     * @param  mixed  $files  TemporaryUploadedFile|TemporaryUploadedFile[]
     */
    private function ingest(string $type, $files): void
    {
        $files = is_array($files) ? $files : [$files];
        $files = array_filter($files);
        if ($files === []) {
            return;
        }

        $payload = [];
        foreach ($files as $f) {
            if (! $f instanceof TemporaryUploadedFile) {
                continue;
            }
            $payload[] = [
                'name'     => $f->getClientOriginalName(),
                'contents' => $f->get(),
                'size'     => $f->getSize(),
            ];
        }
        if ($payload === []) {
            return;
        }

        try {
            $report = app(BulkImageMatcher::class)->processForType($payload, $type);
            $this->results[$type] = $report->toArray();

            Notification::make()
                ->success()
                ->title('Images processed')
                ->body(sprintf(
                    '%d uploaded · %d not matched · %d skipped',
                    $report->totalMatched(),
                    $report->totalUnmatched(),
                    $report->totalSkipped(),
                ))
                ->send();
        } catch (\Throwable $e) {
            Log::error('Bulk image processForType failed', ['type' => $type, 'message' => $e->getMessage()]);
            Notification::make()
                ->danger()
                ->title('Upload failed')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }
}
