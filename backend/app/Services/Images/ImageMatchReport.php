<?php

namespace App\Services\Images;

/**
 * L2 — dry-run + commit result for the bulk image upload (D-L2-6).
 *
 * Per entity type (brands / models / services / categories):
 *   - matched:   filename → entity (+ slug, + stored relative path on commit)
 *   - unmatched: filename (no entity found)
 * Plus a flat `skipped` list with reasons (too large / wrong format /
 * ambiguous model name).
 *
 * `toArray()` produces a Livewire-friendly structure the Blade view
 * renders directly.
 */
class ImageMatchReport
{
    public const TYPES = ['brands', 'models', 'services', 'categories', 'fuel-types'];

    /** @var array<string, array<int, array{filename:string, entity:string, slug:string, stored_path:?string}>> */
    public array $matched = ['brands' => [], 'models' => [], 'services' => [], 'categories' => [], 'fuel-types' => []];

    /** @var array<string, array<int, string>> */
    public array $unmatched = ['brands' => [], 'models' => [], 'services' => [], 'categories' => [], 'fuel-types' => []];

    /** @var array<int, array{filename:string, reason:string}> */
    public array $skipped = [];

    public bool $committed = false;

    public function addMatched(string $type, string $filename, string $entity, string $slug, ?string $storedPath = null): void
    {
        $this->matched[$type][] = [
            'filename'    => $filename,
            'entity'      => $entity,
            'slug'        => $slug,
            'stored_path' => $storedPath,
        ];
    }

    public function addUnmatched(string $type, string $filename): void
    {
        $this->unmatched[$type][] = $filename;
    }

    public function addSkipped(string $filename, string $reason): void
    {
        $this->skipped[] = ['filename' => $filename, 'reason' => $reason];
    }

    public function matchedCount(string $type): int
    {
        return count($this->matched[$type] ?? []);
    }

    public function unmatchedCount(string $type): int
    {
        return count($this->unmatched[$type] ?? []);
    }

    public function totalMatched(): int
    {
        return array_sum(array_map('count', $this->matched));
    }

    public function totalUnmatched(): int
    {
        return array_sum(array_map('count', $this->unmatched));
    }

    public function totalSkipped(): int
    {
        return count($this->skipped);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $summary = [];
        foreach (self::TYPES as $type) {
            $summary[$type] = [
                'matched'   => $this->matchedCount($type),
                'unmatched' => $this->unmatchedCount($type),
            ];
        }

        return [
            'matched'        => $this->matched,
            'unmatched'      => $this->unmatched,
            'skipped'        => $this->skipped,
            'summary'        => $summary,
            'total_matched'  => $this->totalMatched(),
            'total_unmatched' => $this->totalUnmatched(),
            'total_skipped'  => $this->totalSkipped(),
            'committed'      => $this->committed,
        ];
    }
}
