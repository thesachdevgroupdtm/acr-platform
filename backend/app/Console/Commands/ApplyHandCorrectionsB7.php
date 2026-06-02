<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * B7 — apply the 10 operator-locked hand-corrections in one
 * idempotent, auditable pass (D-B7-1 .. D-B7-7).
 *
 * Three sub-steps, all wrapped in a single DB::transaction on the
 * real run:
 *
 *   SP-PEND-1: 3 fluid top-ups moved Performance → Essential
 *   SP-PEND-2: 2 inspections    moved Additional  → Essential
 *   SP-PEND-3: 5 services get a fresh interval_info (NULL-only — never
 *              overwrites operator-edited values)
 *
 * --dry-run prints the exact rows that would change, performs zero
 * writes. Default (no flag) applies the changes.
 *
 * Idempotent: re-running after a successful apply is a clean no-op
 * (each sub-step prints a WARN line "0 rows matched ... already
 * applied?" and continues — D-B7-6). The command does not fail on
 * zero matches.
 *
 * Expected post-state (D-B7-7), printed at the end for verification:
 *   service_inclusions group counts → Essential 467 · Performance 20
 *                                     Additional 56 · NULL 0
 *   5 target services' interval_info → all populated, none NULL
 */
class ApplyHandCorrectionsB7 extends Command
{
    protected $signature = 'corrections:apply-b7 {--dry-run}';

    protected $description = 'Apply 10 operator-locked B7 hand-corrections (group_name moves + interval_info seeds). Idempotent.';

    private const FLUID_PATTERNS = [
        '%brake fluid top up%',
        '%wiper fluid top up%',
        '%battery water top up%',
    ];

    private const INSPECTION_LABELS = [
        'exterior inspection',
        'exterior and interior inspection',
    ];

    /** @var array<string, string> slug => interval_info */
    private const INTERVAL_SEEDS = [
        'front-brake-pad'     => 'After every 40,000 kms (Recommended)',
        'rear-brake-shoes'    => 'After every 40,000 kms (Recommended)',
        'tyre-rotation'       => 'After every 5,000 kms (Recommended)',
        'wheel-balancing'     => 'After every 10,000 kms (Recommended)',
        'complete-wheel-care' => 'After every 10,000 kms (Recommended)',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? '=== B7 DRY-RUN — no writes will be performed ===' : '=== B7 REAL RUN ===');
        $this->line('');

        $runner = function () use ($dryRun): array {
            $changedInclusions = 0;
            $changedIntervals  = 0;

            // ── SP-PEND-1 ──────────────────────────────────────────────
            $this->info('SP-PEND-1 — Move 3 fluid top-ups from Performance → Essential');
            $sp1Query = DB::table('service_inclusions')
                ->where('group_name', 'Performance')
                ->where(function ($q) {
                    foreach (self::FLUID_PATTERNS as $pat) {
                        $q->orWhereRaw('LOWER(label) LIKE ?', [$pat]);
                    }
                });
            $sp1Rows = $sp1Query->get(['id', 'service_id', 'label', 'group_name']);
            $this->printRows($sp1Rows, 'Performance', 'Essential');
            if ($sp1Rows->isEmpty()) {
                $this->warn('  WARN: 0 rows matched for SP-PEND-1 fluid top-ups — already applied?');
            } elseif (! $dryRun) {
                $changedInclusions += $sp1Query->update(['group_name' => 'Essential', 'updated_at' => now()]);
            }
            $this->line('');

            // ── SP-PEND-2 ──────────────────────────────────────────────
            $this->info('SP-PEND-2 — Move 2 inspections from Additional → Essential');
            $sp2Query = DB::table('service_inclusions')
                ->where('group_name', 'Additional')
                ->whereIn(DB::raw('LOWER(TRIM(label))'), self::INSPECTION_LABELS);
            $sp2Rows = $sp2Query->get(['id', 'service_id', 'label', 'group_name']);
            $this->printRows($sp2Rows, 'Additional', 'Essential');
            if ($sp2Rows->isEmpty()) {
                $this->warn('  WARN: 0 rows matched for SP-PEND-2 inspections — already applied?');
            } elseif (! $dryRun) {
                $changedInclusions += $sp2Query->update(['group_name' => 'Essential', 'updated_at' => now()]);
            }
            $this->line('');

            // ── SP-PEND-3 ──────────────────────────────────────────────
            $this->info('SP-PEND-3 — Seed interval_info on 5 services (NULL-only)');
            $headers = ['slug', 'current interval_info', 'proposed', 'will-update?'];
            $tableRows = [];
            foreach (self::INTERVAL_SEEDS as $slug => $newValue) {
                $svc = DB::table('services')->where('slug', $slug)->first(['id', 'slug', 'interval_info']);
                if (! $svc) {
                    $tableRows[] = [$slug, '— (no row)', $newValue, 'SKIP (missing)'];
                    continue;
                }
                if ($svc->interval_info !== null && $svc->interval_info !== '') {
                    $tableRows[] = [$slug, $svc->interval_info, $newValue, 'SKIP (already set — never overwrite)'];
                    continue;
                }
                $tableRows[] = [$slug, 'NULL', $newValue, $dryRun ? 'WOULD-UPDATE' : 'UPDATE'];
                if (! $dryRun) {
                    DB::table('services')
                        ->where('id', $svc->id)
                        ->whereNull('interval_info')
                        ->update(['interval_info' => $newValue, 'updated_at' => now()]);
                    $changedIntervals++;
                }
            }
            $this->table($headers, $tableRows);

            $eligibleNow = count(array_filter($tableRows, fn ($r) => str_starts_with((string) $r[3], 'WOULD-UPDATE') || str_starts_with((string) $r[3], 'UPDATE')));
            if ($eligibleNow === 0) {
                $this->warn('  WARN: 0 services eligible for SP-PEND-3 interval_info seeding — already applied?');
            }
            $this->line('');

            return ['inclusions' => $changedInclusions, 'intervals' => $changedIntervals];
        };

        $result = $dryRun
            ? $runner()
            : DB::transaction($runner);

        // ── Audit trail (D-B7-7) ──────────────────────────────────────
        $this->info('=== POST-RUN AUDIT TRAIL ===');
        $this->info('service_inclusions group_name distribution (expected: Essential 467 · Performance 20 · Additional 56 · NULL 0):');
        $dist = [];
        foreach (DB::table('service_inclusions')->select('group_name', DB::raw('count(*) as c'))->groupBy('group_name')->get() as $r) {
            $dist[] = [$r->group_name ?? 'NULL', $r->c];
        }
        $this->table(['group_name', 'count'], $dist);

        $this->info('5 target services — interval_info (expected: all populated, none NULL):');
        $svcRows = [];
        foreach (array_keys(self::INTERVAL_SEEDS) as $slug) {
            $svc = DB::table('services')->where('slug', $slug)->first(['slug', 'interval_info']);
            $svcRows[] = [$slug, $svc ? ($svc->interval_info ?? 'NULL') : 'MISSING'];
        }
        $this->table(['slug', 'interval_info'], $svcRows);

        $this->line('');
        $this->info(sprintf(
            'B7 %s. Updated %d inclusion groups + %d interval_info rows.',
            $dryRun ? 'dry-run complete' : 'closed',
            $result['inclusions'],
            $result['intervals']
        ));

        return self::SUCCESS;
    }

    /** @param \Illuminate\Support\Collection $rows */
    private function printRows($rows, string $from, string $to): void
    {
        if ($rows->isEmpty()) {
            return;
        }
        $tableRows = [];
        foreach ($rows as $r) {
            $tableRows[] = [$r->id, $r->service_id, $r->label, $r->group_name, '→ ' . $to];
        }
        $this->table(['id', 'service_id', 'label', "from ({$from})", 'to'], $tableRows);
    }
}
