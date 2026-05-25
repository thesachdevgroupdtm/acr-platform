<?php

namespace App\Services\Imports\Strategies;

use Illuminate\Database\Eloquent\Model;

/**
 * Phase 4.3.5 (Sub-phase 1.2) — Levenshtein-based fuzzy match per
 * D-1.2-3:
 *
 *   normalize(s) = strtolower(strip_non_alphanum(trim(s)))
 *   similarity(a, b) = 1 - levenshtein(a, b) / max(len(a), len(b))
 *
 * Default threshold is 0.85. Above → reuse existing entity. Below →
 * the caller (AutoBootstrapResolver) treats it as a "would create" /
 * "create" hit.
 *
 * Stateless / pure — safe for Laravel-container resolution as a
 * singleton, but registered as a normal binding so test code can
 * override if needed.
 */
class FuzzyMatcher
{
    public const DEFAULT_THRESHOLD = 0.85;

    /**
     * Walk the candidate iterable and return the highest-similarity
     * hit ≥ $threshold. Returns null when nothing clears the bar.
     *
     * @param  iterable<int, Model>  $candidates
     * @return array{entity: Model, similarity: float}|null
     */
    public function findBest(
        string $input,
        iterable $candidates,
        string $field,
        float $threshold = self::DEFAULT_THRESHOLD,
    ): ?array {
        $normInput = $this->normalize($input);
        if ($normInput === '') {
            return null;
        }

        $bestEntity     = null;
        $bestSimilarity = 0.0;

        foreach ($candidates as $candidate) {
            $candidateValue = $candidate->{$field} ?? null;
            if (! is_string($candidateValue) || $candidateValue === '') {
                continue;
            }

            $sim = $this->similarity($input, $candidateValue);
            if ($sim > $bestSimilarity) {
                $bestSimilarity = $sim;
                $bestEntity     = $candidate;
            }
        }

        if ($bestEntity === null || $bestSimilarity < $threshold) {
            return null;
        }

        return ['entity' => $bestEntity, 'similarity' => $bestSimilarity];
    }

    /**
     * Normalised Levenshtein similarity in [0.0, 1.0].
     *
     * PHP's native levenshtein() hard-caps both args at 255 chars; for
     * longer strings we fall back to similar_text()'s percentage so
     * the function never throws on edge inputs.
     */
    public function similarity(string $a, string $b): float
    {
        $na = $this->normalize($a);
        $nb = $this->normalize($b);

        if ($na === '' || $nb === '') {
            return 0.0;
        }
        if ($na === $nb) {
            return 1.0;
        }

        if (strlen($na) > 255 || strlen($nb) > 255) {
            similar_text($na, $nb, $pct);
            return $pct / 100.0;
        }

        $distance = levenshtein($na, $nb);
        $maxLen   = max(strlen($na), strlen($nb));

        return 1.0 - ($distance / $maxLen);
    }

    public function normalize(string $value): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9]/i', '', $value) ?? ''));
    }
}
