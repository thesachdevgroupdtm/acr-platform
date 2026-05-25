<?php

namespace App\Console\Commands;

use App\Models\ServiceInclusion;
use Illuminate\Console\Command;

/**
 * Service-pages redesign Phase 1.5 (D-1.5-3/4) — keyword auto-classifier
 * that buckets inclusion labels into "Essential" / "Performance" /
 * "Additional".
 *
 * Re-runnable and NULL-only: it ONLY sets group_name where it is
 * currently NULL, so it never overwrites an operator's manual choice.
 * Not part of any migration — a separate, idempotent artisan command.
 *
 *   php artisan inclusions:autogroup            # apply to NULL rows
 *   php artisan inclusions:autogroup --dry-run  # preview, write nothing
 */
class AutogroupInclusions extends Command
{
    protected $signature = 'inclusions:autogroup {--dry-run : Print the proposed label→group mapping without writing}';

    protected $description = 'Auto-classify ungrouped service inclusions into Essential/Performance/Additional (NULL-only, re-runnable).';

    /**
     * D-1.5-4 keyword map (case-insensitive substring on the label).
     * Performance is checked FIRST, then Additional, else Essential.
     */
    private const PERFORMANCE = [
        'spark', 'plug', 'coolant', 'injector', 'throttle',
        'top up', 'top-up', 'performance', 'fuel system', 'carbon',
    ];

    private const ADDITIONAL = [
        'wash', 'vacuum', 'polish', 'wax', 'foam', 'interior', 'exterior',
        'detailing', 'freshener', 'sanitiz', 'rubbing', 'teflon', 'ceramic',
        'dressing',
    ];

    /**
     * Pure, side-effect-free classifier. Returns the canonical group
     * string for a label. Shared by the command + tests so there is one
     * source of truth for the keyword rules (D-1.5-4).
     */
    public static function classify(string $label): string
    {
        $l = mb_strtolower($label);

        foreach (self::PERFORMANCE as $kw) {
            if (str_contains($l, $kw)) {
                return 'Performance';
            }
        }
        foreach (self::ADDITIONAL as $kw) {
            if (str_contains($l, $kw)) {
                return 'Additional';
            }
        }

        return 'Essential';
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $rows = ServiceInclusion::query()
            ->whereNull('group_name')
            ->orderBy('service_id')
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'service_id', 'label', 'group_name']);

        if ($rows->isEmpty()) {
            $this->info('No ungrouped inclusions (group_name IS NULL). Nothing to do.');
            return self::SUCCESS;
        }

        $counts = ['Essential' => 0, 'Performance' => 0, 'Additional' => 0];
        $preview = [];

        foreach ($rows as $row) {
            $group = self::classify((string) $row->label);
            $counts[$group]++;
            $preview[] = [$row->label, $group];

            if (! $dryRun) {
                // NULL-only guarded by the query above; set + save.
                $row->group_name = $group;
                $row->save();
            }
        }

        if ($dryRun) {
            $this->table(['Label', 'Proposed group'], $preview);
            $this->info(sprintf(
                'DRY RUN — %d ungrouped inclusion(s) would be set: Essential=%d, Performance=%d, Additional=%d. Nothing written.',
                $rows->count(), $counts['Essential'], $counts['Performance'], $counts['Additional']
            ));
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Grouped %d inclusion(s): Essential=%d, Performance=%d, Additional=%d. (Only NULL rows touched; re-run is a no-op.)',
            $rows->count(), $counts['Essential'], $counts['Performance'], $counts['Additional']
        ));

        return self::SUCCESS;
    }
}
