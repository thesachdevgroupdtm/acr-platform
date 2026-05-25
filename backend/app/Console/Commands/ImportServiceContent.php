<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\ServiceInclusion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Service-pages redesign — import old acr2025 service content into
 * acr_v3 from the two phpMyAdmin dumps at
 * storage/app/imports/{sceduled_packages,package_specification}.sql.
 *
 * SAFE by construction (per /SERVICE_IMPORT_DIAGNOSTIC.md + D-IMP-*):
 *   - Match strictly by services.slug (exact + a hard-coded near map;
 *     NO runtime fuzzy matching). One known skip (rear-shock).
 *   - Inclusions: INSERT only when the service has ZERO inclusions
 *     (empty-guard) → re-run never duplicates.
 *   - time / warrenty / recommended / interval: NULL-only UPDATE
 *     (never overwrite an existing non-null value).
 *   - warrenty/recommended are pattern-filtered (the old columns are
 *     dirty — some hold symptom text); non-matching values are left
 *     NULL for the operator.
 *   - SKIP price / image / note entirely.
 *   - All writes inside one DB transaction; --dry-run writes nothing.
 *   - NEVER touches service_prices, slugs, or categories.
 *
 *   php artisan service-content:import --dry-run
 *   php artisan service-content:import
 */
class ImportServiceContent extends Command
{
    protected $signature = 'service-content:import {--dry-run : Compute and print the full plan without writing}';

    protected $description = 'Import old service content (inclusions, time, pattern-validated warranty/recommended/interval) into acr_v3 by slug. Additive + NULL-only + re-runnable.';

    /** D-IMP-2 — corrected near map: old slug → current slug (literal lookup). */
    public const NEAR_MAP = [
        'flat-bed-towing'             => 'flat-bed-towing-upto-10km',
        'wheel-lift-towing-10-kms'    => 'wheel-lift-towing-upto-10km',
        'rear-brake-shoes-replacement' => 'rear-brake-shoes',
        'boot-paint'                  => 'boot-point',
        'left-quarter-panel-paint'    => 'left-quarter-pannel-paint',
        'right-quarter-panel-paint'   => 'right-quarter-pannel-paint',
        'rat-pest'                    => 'ratpest',
        'front-windshield-replacement' => 'front-windshiled-replacement',
        'rear-windshield-replacement'  => 'rearwindshiled-replacement',
        'clutch-overhaul'             => 'clutch-overall',
        'accidental-claim'            => 'accidential-claim',
        'front-brake-disc-replacement' => 'front-brake-disc',
        'front-brake-pad-replacement'  => 'front-brake-pad',
        'front-bumper-paint'          => 'front-bumper',
        'rear-bumper-paint'           => 'rear-bumper',
        'bonnet-paint'                => 'bonnet',
        'pre-owned-car-inspection'    => 'second-hand-car-inspection',
    ];

    /** D-IMP-3 — old slug(s) with no current target: skip, never create. */
    public const SKIP_SLUGS = ['rear-shock-absorber-replacement'];

    /* ───────────────────────── pure helpers (unit-testable) ───────────────────────── */

    /**
     * Resolve an old package slug to its current service slug.
     * Returns null when the package must be SKIPPED (D-IMP-3).
     */
    public static function resolveTargetSlug(string $oldSlug): ?string
    {
        if (in_array($oldSlug, self::SKIP_SLUGS, true)) {
            return null;
        }
        return self::NEAR_MAP[$oldSlug] ?? $oldSlug;
    }

    /** D-IMP-6 — warranty value must look like a real warranty. */
    public static function warrantyPasses(?string $v): bool
    {
        return is_string($v) && $v !== ''
            && preg_match('/warranty|kms?|months?|years?/i', $v) === 1;
    }

    /** D-IMP-6 — recommended value must look like real recommendation copy. */
    public static function recommendedPasses(?string $v): bool
    {
        return is_string($v) && $v !== ''
            && preg_match('/after every|recommended|kms?|months?/i', $v) === 1;
    }

    /** D-IMP-7 — seed interval_info from recommended copy when it carries a km cadence. */
    public static function intervalFrom(?string $recommended): ?string
    {
        if (is_string($recommended) && $recommended !== ''
            && preg_match('/every\s+[\d,]+\s*kms?/i', $recommended) === 1) {
            return $recommended; // keep original string (no normalization)
        }
        return null;
    }

    /** D-IMP-5 — Hour→hours, Day→days. */
    public static function mapTimeUnit(?string $option): ?string
    {
        return match (strtolower((string) $option)) {
            'hour', 'hours' => 'hours',
            'day', 'days'   => 'days',
            default         => null,
        };
    }

    /**
     * Tokenize a MySQL `INSERT ... VALUES (...),(...);` block into rows of
     * scalar fields (NULL → null). Handles backslash escapes + doubled ''.
     *
     * @return array<int, array<int, ?string>>
     */
    public static function parseInsertValues(string $sql): array
    {
        $vp = stripos($sql, 'VALUES');
        if ($vp === false) {
            return [];
        }
        $s = substr($sql, $vp + 6);
        $n = strlen($s);
        $i = 0;
        $rows = [];
        while ($i < $n) {
            if ($s[$i] !== '(') { $i++; continue; }
            $i++;
            $fields = [];
            while ($i < $n) {
                while ($i < $n && ctype_space($s[$i])) $i++;
                if ($s[$i] === ')') { $i++; break; }
                if ($s[$i] === "'") {
                    $i++; $val = '';
                    while ($i < $n) {
                        $c = $s[$i];
                        if ($c === '\\') { $val .= ($s[$i + 1] ?? ''); $i += 2; continue; }
                        if ($c === "'") {
                            if (($s[$i + 1] ?? '') === "'") { $val .= "'"; $i += 2; continue; }
                            $i++; break;
                        }
                        $val .= $c; $i++;
                    }
                    $fields[] = $val;
                } else {
                    $tok = '';
                    while ($i < $n && $s[$i] !== ',' && $s[$i] !== ')') { $tok .= $s[$i]; $i++; }
                    $tok = trim($tok);
                    $fields[] = (strtoupper($tok) === 'NULL' || $tok === '') ? null : $tok;
                }
                while ($i < $n && ctype_space($s[$i])) $i++;
                if (($s[$i] ?? '') === ',') { $i++; continue; }
                if (($s[$i] ?? '') === ')') { $i++; break; }
            }
            $rows[] = $fields;
            while ($i < $n && ($s[$i] === ',' || ctype_space($s[$i]))) $i++;
            if (($s[$i] ?? '') === ';') break;
        }
        return $rows;
    }

    /* ───────────────────────── command ───────────────────────── */

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $pkgPath  = storage_path('app/imports/sceduled_packages.sql');
        $specPath = storage_path('app/imports/package_specification.sql');
        if (! is_file($pkgPath) || ! is_file($specPath)) {
            $this->error('Source dumps not found in storage/app/imports/. Aborting.');
            return self::FAILURE;
        }

        // ---- parse (read-only) ----
        $pkgRows  = self::parseInsertValues(file_get_contents($pkgPath));
        $specRows = self::parseInsertValues(file_get_contents($specPath));

        // packages: id(0), slug(5), warrenty_info(9), recommended_info(10), time_takes(12), time_takes_option(14)
        $packages = [];
        foreach ($pkgRows as $r) {
            $packages[] = [
                'id'                => $r[0],
                'slug'              => (string) ($r[5] ?? ''),
                'warrenty_info'     => $r[9]  ?? null,
                'recommended_info'  => $r[10] ?? null,
                'time_takes'        => $r[12] ?? null,
                'time_takes_option' => $r[14] ?? null,
            ];
        }

        // specs grouped by sp_id, preserving source order: sp_id(1), specification(2)
        $specsBySp = [];
        foreach ($specRows as $r) {
            $sp = $r[1];
            if ($sp === null) continue;
            $specsBySp[$sp][] = (string) ($r[2] ?? '');
        }

        $this->info("Parsed {$this->n($packages)} packages, {$this->n($specRows)} inclusion rows.");

        // ---- resolve slugs + build plan (read-only) ----
        $svcBySlug = Service::query()->get()->keyBy('slug');

        $skipped   = [];   // old slugs intentionally skipped (rear-shock)
        $unmatched = [];   // old slugs whose target service is missing (unexpected)
        $plan = [
            'inclusions'   => [],  // [target_slug => [labels...]]
            'guarded'      => [],  // [target_slug] already had inclusions
            'time'         => [],  // [target_slug => "12 hours"]
            'time_skip'    => [],  // [target_slug => reason]
            'warranty'     => [],  // [target_slug => value]
            'warranty_skip'=> [],  // [target_slug => value rejected by pattern]
            'recommended'  => [],
            'recommended_skip' => [],
            'interval'     => [],  // [target_slug => value]
        ];

        foreach ($packages as $p) {
            $target = self::resolveTargetSlug($p['slug']);
            if ($target === null) {
                $skipped[] = $p['slug'];
                continue;
            }
            $svc = $svcBySlug->get($target);
            if (! $svc) {
                $unmatched[] = "{$p['slug']} → {$target}";
                continue;
            }

            // inclusions (empty-guard)
            $incl = $specsBySp[$p['id']] ?? [];
            if (! empty($incl)) {
                if ($svc->inclusions()->count() > 0) {
                    $plan['guarded'][] = $target;
                } else {
                    $plan['inclusions'][$target] = $incl;
                }
            }

            // time (NULL-only)
            if ($p['time_takes'] !== null && $p['time_takes'] !== '') {
                if ($svc->time_takes === null || $svc->time_takes === '') {
                    $unit = self::mapTimeUnit($p['time_takes_option']);
                    $plan['time'][$target] = trim($p['time_takes'] . ' ' . (string) $unit);
                } else {
                    $plan['time_skip'][$target] = 'already set';
                }
            }

            // warranty (pattern + NULL-only)
            if ($p['warrenty_info'] !== null && $p['warrenty_info'] !== '') {
                if ($svc->warrenty_info !== null && $svc->warrenty_info !== '') {
                    // already set — leave it
                } elseif (self::warrantyPasses($p['warrenty_info'])) {
                    $plan['warranty'][$target] = $p['warrenty_info'];
                } else {
                    $plan['warranty_skip'][$target] = $p['warrenty_info'];
                }
            }

            // recommended (pattern + NULL-only)
            if ($p['recommended_info'] !== null && $p['recommended_info'] !== '') {
                if ($svc->recommended_info !== null && $svc->recommended_info !== '') {
                    // already set
                } elseif (self::recommendedPasses($p['recommended_info'])) {
                    $plan['recommended'][$target] = $p['recommended_info'];
                } else {
                    $plan['recommended_skip'][$target] = $p['recommended_info'];
                }
            }

            // interval (seed from recommended; NULL-only)
            $interval = self::intervalFrom($p['recommended_info']);
            if ($interval !== null && ($svc->interval_info === null || $svc->interval_info === '')) {
                $plan['interval'][$target] = $interval;
            }
        }

        // ---- safety gate: unexpected unmatched → STOP, write nothing ----
        if (! empty($unmatched)) {
            $this->error('STOP — these old slugs resolved to NO current service (map/DB mismatch):');
            foreach ($unmatched as $u) $this->line("   - {$u}");
            $this->error('No writes performed. Fix the map or DB and re-run.');
            return self::FAILURE;
        }

        // ---- print plan ----
        $inclSvcCount   = count($plan['inclusions']);
        $inclRowCount   = array_sum(array_map('count', $plan['inclusions']));
        $this->newLine();
        $this->info($dryRun ? '===== DRY RUN — planned changes (nothing written) =====' : '===== APPLYING =====');
        $this->line("Inclusions: {$inclSvcCount} services → {$inclRowCount} rows" . (count($plan['guarded']) ? " (empty-guard skipped " . count($plan['guarded']) . " already-populated services)" : ''));
        $this->line("time_takes/time_unit: " . count($plan['time']) . " services" . (count($plan['time_skip']) ? " (" . count($plan['time_skip']) . " already set)" : ''));
        $this->line("warrenty_info: " . count($plan['warranty']) . " services set, " . count($plan['warranty_skip']) . " rejected by pattern");
        $this->line("recommended_info: " . count($plan['recommended']) . " services set, " . count($plan['recommended_skip']) . " rejected by pattern");
        $this->line("interval_info: " . count($plan['interval']) . " services seeded");
        $this->line("skipped packages (no target): " . (count($skipped) ? implode(', ', $skipped) : 'none'));

        if (! empty($plan['warranty_skip'])) {
            $this->newLine();
            $this->warn('warrenty_info values REJECTED by pattern (left NULL for operator):');
            foreach ($plan['warranty_skip'] as $slug => $v) $this->line("   [{$slug}] " . json_encode($v));
        }
        if (! empty($plan['recommended_skip'])) {
            $this->newLine();
            $this->warn('recommended_info values REJECTED by pattern (left NULL for operator):');
            foreach ($plan['recommended_skip'] as $slug => $v) $this->line("   [{$slug}] " . json_encode($v));
        }
        if (! empty($plan['interval'])) {
            $this->newLine();
            $this->info('interval_info seeded from recommended (sample):');
            foreach (array_slice($plan['interval'], 0, 8, true) as $slug => $v) $this->line("   [{$slug}] " . json_encode($v));
        }

        if ($dryRun) {
            $this->newLine();
            $this->info('DRY RUN complete — no database changes made.');
            return self::SUCCESS;
        }

        // ---- apply (transaction) ----
        try {
            DB::transaction(function () use ($plan, $svcBySlug) {
                foreach ($plan['inclusions'] as $slug => $labels) {
                    $svc = $svcBySlug->get($slug);
                    $pos = 1;
                    foreach ($labels as $label) {
                        ServiceInclusion::create([
                            'service_id' => $svc->id,
                            'label'      => $label,
                            'group_name' => null,
                            'image'      => null,
                            'position'   => $pos++,
                        ]);
                    }
                }
                foreach ($plan['time'] as $slug => $val) {
                    [$takes, $unit] = array_pad(explode(' ', $val, 2), 2, null);
                    $svcBySlug->get($slug)->forceFill([
                        'time_takes' => $takes,
                        'time_unit'  => $unit,
                    ])->save();
                }
                foreach ($plan['warranty'] as $slug => $val) {
                    $svcBySlug->get($slug)->forceFill(['warrenty_info' => $val])->save();
                }
                foreach ($plan['recommended'] as $slug => $val) {
                    $svcBySlug->get($slug)->forceFill(['recommended_info' => $val])->save();
                }
                foreach ($plan['interval'] as $slug => $val) {
                    $svcBySlug->get($slug)->forceFill(['interval_info' => $val])->save();
                }
            });
        } catch (\Throwable $e) {
            $this->error('Import failed, rolled back: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Committed. Verification counts:');
        $this->line('  services: ' . Service::count());
        $this->line('  inclusions total: ' . ServiceInclusion::count());
        $this->line('  services with >=1 inclusion: ' . Service::has('inclusions')->count());
        $this->line('  with time_takes: ' . Service::whereNotNull('time_takes')->count());
        $this->line('  with warrenty_info: ' . Service::whereNotNull('warrenty_info')->count());
        $this->line('  with recommended_info: ' . Service::whereNotNull('recommended_info')->count());
        $this->line('  with interval_info: ' . Service::whereNotNull('interval_info')->count());
        $this->line('  inclusions with group_name NULL: ' . ServiceInclusion::whereNull('group_name')->count() . ' (autogroup runs next)');

        return self::SUCCESS;
    }

    private function n($arr): int
    {
        return is_countable($arr) ? count($arr) : 0;
    }
}
