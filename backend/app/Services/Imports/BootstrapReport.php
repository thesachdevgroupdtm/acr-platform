<?php

namespace App\Services\Imports;

use App\Services\Imports\DTOs\EntitySummary;

/**
 * Phase 4.3.5 (Sub-phase 1.2) — the contract between AutoBootstrapResolver
 * and the operator-facing Filament page. Same shape for both phases:
 *
 *   resolveDryRun()     → isDryRun=true,  importId=null
 *   resolveAndPersist() → isDryRun=false, importId=<created>
 *
 * The Filament page serialises this via toArray() into a Livewire
 * property the Blade view reads to render the bootstrap summary card.
 */
final class BootstrapReport
{
    public function __construct(
        public readonly EntitySummary $brands,
        public readonly EntitySummary $models,
        public readonly EntitySummary $fuelTypes,
        public readonly EntitySummary $services,
        public readonly EntitySummary $categories,
        public readonly bool $isDryRun,
        public readonly ?int $importId,
    ) {
    }

    /**
     * Total new entities across every dimension. Drives the bootstrap
     * card's "show / hide" decision in the Blade view — when zero,
     * we suppress the card entirely (steady-state run).
     */
    public function totalNewEntities(): int
    {
        return ($this->isDryRun
                ? $this->brands->wouldCreate     + $this->models->wouldCreate
                  + $this->fuelTypes->wouldCreate + $this->services->wouldCreate
                  + $this->categories->wouldCreate
                : $this->brands->created     + $this->models->created
                  + $this->fuelTypes->created + $this->services->created
                  + $this->categories->created);
    }

    public function totalMatchedExisting(): int
    {
        return $this->brands->matchedExisting
             + $this->models->matchedExisting
             + $this->fuelTypes->matchedExisting
             + $this->services->matchedExisting
             + $this->categories->matchedExisting;
    }

    public function toArray(): array
    {
        return [
            'brands'             => $this->brands->toArray(),
            'models'             => $this->models->toArray(),
            'fuelTypes'          => $this->fuelTypes->toArray(),
            'services'           => $this->services->toArray(),
            'categories'         => $this->categories->toArray(),
            'isDryRun'           => $this->isDryRun,
            'importId'           => $this->importId,
            'totalNewEntities'   => $this->totalNewEntities(),
            'totalMatchedExisting' => $this->totalMatchedExisting(),
        ];
    }
}
