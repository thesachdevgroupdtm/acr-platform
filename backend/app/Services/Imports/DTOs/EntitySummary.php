<?php

namespace App\Services\Imports\DTOs;

/**
 * Phase 4.3.5 (Sub-phase 1.2) — per-entity rollup inside BootstrapReport.
 *
 * Same DTO shape is used for both dry-run and persist phases. The
 * fields' semantics depend on the parent report's isDryRun flag:
 *
 *   isDryRun=true   → wouldCreate populated, created=0, createdIds=[]
 *   isDryRun=false  → created populated, wouldCreate=0, createdIds set
 *
 * `previewNames` is the list of names that WOULD be (or WERE) created
 * — the human-readable evidence the operator sees in the "View
 * details" expansion of the bootstrap summary card.
 */
final class EntitySummary
{
    /**
     * @param  array<int, string>  $previewNames
     * @param  array<int, int>     $createdIds
     */
    public function __construct(
        public readonly int $matchedExisting,
        public readonly int $wouldCreate,
        public readonly int $created,
        public readonly array $previewNames,
        public readonly array $createdIds,
    ) {
    }

    public static function dryRun(int $matched, array $previewNames): self
    {
        return new self(
            matchedExisting: $matched,
            wouldCreate:     count($previewNames),
            created:         0,
            previewNames:    $previewNames,
            createdIds:      [],
        );
    }

    public static function persisted(int $matched, array $createdNames, array $createdIds): self
    {
        return new self(
            matchedExisting: $matched,
            wouldCreate:     0,
            created:         count($createdIds),
            previewNames:    $createdNames,
            createdIds:      $createdIds,
        );
    }

    public function toArray(): array
    {
        return [
            'matchedExisting' => $this->matchedExisting,
            'wouldCreate'     => $this->wouldCreate,
            'created'         => $this->created,
            'previewNames'    => $this->previewNames,
            'createdIds'      => $this->createdIds,
        ];
    }
}
