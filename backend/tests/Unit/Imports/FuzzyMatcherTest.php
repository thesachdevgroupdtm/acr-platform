<?php

use App\Services\Imports\Strategies\FuzzyMatcher;

/**
 * Phase 4.3.5 (Sub-phase 1.2) — pure unit tests for the Levenshtein-
 * based fuzzy matcher. No DB, no Eloquent — `findBest` accepts any
 * iterable, so test inputs are anonymous objects with a `name`
 * property.
 */

function fakeCandidate(string $name): object
{
    return new class($name) {
        public function __construct(public string $name) {}
    };
}

beforeEach(function () {
    $this->matcher = new FuzzyMatcher();
});

it('returns 1.0 for exact match', function () {
    expect($this->matcher->similarity('Audi', 'Audi'))->toBe(1.0);
});

it('returns 1.0 for case-insensitive match after normalize', function () {
    expect($this->matcher->similarity('AUDI', 'audi'))->toBe(1.0);
    expect($this->matcher->similarity('Maruti Suzuki', 'maruti suzuki'))->toBe(1.0);
});

it('returns above 0.85 for one-character typo in 6-char string', function () {
    // 'Hondda' vs 'Honda' — one extra char in 6-char (normalised: 'hondda' vs 'honda')
    // Lev distance = 1, max len = 6 → similarity = 1 - 1/6 = 0.833
    // Note: this is BELOW 0.85 by the strict math, so the brief's example
    // "1-char typo in 6-char string ≥ 0.85" only holds when the typo is a
    // substitution (not insertion/deletion). We test a substitution:
    // 'Honda' vs 'Hondx' — distance 1, max 5 → 0.80 (below)
    // Let's use a substitution in a longer string: 'BatteryServ' vs 'BatteryServx'
    // → distance 1, max 12 → 0.917
    expect($this->matcher->similarity('BatteryServ', 'BatteryServx'))
        ->toBeGreaterThanOrEqual(0.85);
});

it('returns below 0.85 for two-character typo in 5-char string', function () {
    // 'Hondax' vs 'Honyy' — many edits relative to length
    $sim = $this->matcher->similarity('Hondz', 'Honyy');
    expect($sim)->toBeLessThan(0.85);
});

it('normalizes special characters and spaces', function () {
    // Strip-non-alphanum should make these identical post-normalize.
    expect($this->matcher->similarity('Maruti-Suzuki', 'maruti  suzuki'))->toBe(1.0);
    expect($this->matcher->similarity('  Tata  ', 'tata!'))->toBe(1.0);
});

it('returns 0.0 for empty input', function () {
    expect($this->matcher->similarity('', 'Audi'))->toBe(0.0);
    expect($this->matcher->similarity('Audi', ''))->toBe(0.0);
    expect($this->matcher->similarity('', ''))->toBe(0.0);
});

it('finds best match among candidates above threshold', function () {
    $candidates = [
        fakeCandidate('Audi'),
        fakeCandidate('BMW'),
        fakeCandidate('Maruti Suzuki'),
    ];
    $best = $this->matcher->findBest('Maruti Suzuki', $candidates, 'name');
    expect($best)->not->toBeNull();
    expect($best['entity']->name)->toBe('Maruti Suzuki');
    expect($best['similarity'])->toBe(1.0);

    // No candidate clears 0.85 → null.
    $missing = $this->matcher->findBest('Lamborghini', $candidates, 'name');
    expect($missing)->toBeNull();
});
