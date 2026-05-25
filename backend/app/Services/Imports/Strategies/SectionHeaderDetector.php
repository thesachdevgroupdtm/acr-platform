<?php

namespace App\Services\Imports\Strategies;

/**
 * Phase 4.3.5 (Sub-phase 1.2) — section-header detection for the
 * auto-bootstrap pipeline. Walks raw Excel rows (no heading-row
 * formatter, raw cell values) and identifies "banner rows" that
 * group service columns to their right under a category label.
 *
 * Detection order per D-1.2-4 (first match wins):
 *   1. Single non-empty cell whose value matches KNOWN_VOCABULARY
 *   2. Single non-empty cell preceded by a blank row (visual banner)
 *   3. Single non-empty cell (sparse-row heuristic)
 *   4. Fallback: caller assigns "Imported Services"
 *
 * Cell-formatting heuristic (bold/colored) — not implemented here:
 * Maatwebsite\Excel's ToArray reader returns raw values only; reading
 * formatting requires dropping into PhpSpreadsheet directly. The
 * three text-based heuristics catch the documented production
 * layouts; formatting fallback is a future enhancement.
 *
 * The detector is stateless — output is a function of input rows
 * only. Safe for container singleton.
 */
class SectionHeaderDetector
{
    public const KNOWN_VOCABULARY = [
        'Battery', 'Car Care', 'Paint', 'AC Service',
        'Suspension', 'Brake', 'Clutch', 'Emergency Services',
        'Detailing', 'Engine', 'Transmission', 'Tyres',
        'Wheel Alignment', 'Body Work', 'Electricals',
        'Mechanical', 'Insurance', 'Inspection',
    ];

    public const FALLBACK_CATEGORY = 'Imported Services';

    /**
     * Returns a map of column-index → section-name for every column
     * that has a section anchor at-or-above its position.
     *
     * Columns without an anchor are absent from the map; the caller
     * assigns them to FALLBACK_CATEGORY.
     *
     * @param  iterable<int, array<int|string, mixed>>  $rows
     * @return array<int, string>  column index → section name
     */
    public function detect(iterable $rows): array
    {
        $rowsArray = is_array($rows) ? $rows : iterator_to_array($rows, false);

        // Cap the rightmost column we ever assign to (the widest row in
        // the input set). Without this, a rightmost banner cell with no
        // banner to its right would loop to PHP_INT_MAX. Maatwebsite
        // sheets are well under a few hundred columns in practice.
        $maxCol = -1;
        foreach ($rowsArray as $r) {
            if (! is_array($r) || empty($r)) {
                continue;
            }
            $maxCol = max($maxCol, max(array_keys($r)));
        }
        if ($maxCol < 0) {
            return [];
        }

        $bannerCells = $this->extractBannerCells($rowsArray);
        if (empty($bannerCells)) {
            return [];
        }

        // For each column index in any banner row, the section is the
        // banner cell at that exact column. Columns to the right of a
        // banner cell *up to the next banner cell* inherit it.
        $columnToSection = [];

        foreach ($bannerCells as $rowIdx => $bannersByCol) {
            ksort($bannersByCol);
            $bannerCols = array_keys($bannersByCol);

            foreach ($bannersByCol as $colIdx => $sectionName) {
                $nextBannerCol = null;
                foreach ($bannerCols as $candidate) {
                    if ($candidate > $colIdx) {
                        $nextBannerCol = $candidate;
                        break;
                    }
                }

                $rightBound = $nextBannerCol ?? ($maxCol + 1);
                for ($c = $colIdx; $c < $rightBound; $c++) {
                    // Later banner rows override earlier ones (closer-to-
                    // data anchors win — common in stacked spreadsheets).
                    $columnToSection[$c] = $this->canonicalise($sectionName);
                }
            }
        }

        return $columnToSection;
    }

    /**
     * Identify rows that look like banners. Returns nested array:
     *   [row_idx => [col_idx => raw cell value, ...], ...]
     *
     * A row qualifies if EITHER:
     *   - vocabulary hit on any of its non-empty cells, OR
     *   - sparse-row heuristic (≤ 30% non-empty + preceded by a fully
     *     blank row OR is the first row).
     *
     * Rows that contain recognised data-header keywords (Make, Model,
     * Fuel_Type, etc.) are explicitly excluded — they're the column
     * header row, not a section banner.
     */
    private function extractBannerCells(array $rows): array
    {
        $out = [];
        $dataHeaderKeywords = ['carid', 'make', 'brand', 'model', 'fueltype', 'fuel', 'segment'];

        $prevWasBlank = true; // treat virtual row -1 as blank
        foreach ($rows as $idx => $row) {
            if (! is_array($row)) {
                continue;
            }

            $nonEmpty = [];
            foreach ($row as $colIdx => $cell) {
                $s = trim((string) $cell);
                if ($s !== '') {
                    $nonEmpty[(int) $colIdx] = $s;
                }
            }

            $total       = count($row);
            $nonEmptyCnt = count($nonEmpty);
            $isAllBlank  = $nonEmptyCnt === 0;

            // Exclude the data-header row.
            $isDataHeader = false;
            foreach ($nonEmpty as $s) {
                if (in_array($this->normalizeForKeyword($s), $dataHeaderKeywords, true)) {
                    $isDataHeader = true;
                    break;
                }
            }
            if ($isDataHeader) {
                $prevWasBlank = $isAllBlank;
                continue;
            }

            // Heuristic 1 — vocabulary match on any non-empty cell.
            $vocabMatched = [];
            foreach ($nonEmpty as $colIdx => $val) {
                if ($this->matchesKnownVocabulary($val)) {
                    $vocabMatched[$colIdx] = $val;
                }
            }
            if (! empty($vocabMatched)) {
                $out[(int) $idx] = $vocabMatched;
                $prevWasBlank = $isAllBlank;
                continue;
            }

            // Heuristics 2 + 3 — sparse banner row. Accepts one or more
            // non-empty cells as long as the row is sparse (≤30% filled)
            // OR was preceded by a blank row. Multi-banner rows are
            // common in the wild — e.g. `[_, _, _, _, _, "Regular Car
            // Service", _, _, "Car AC Service & Repair", _, _]` has two
            // banners spanning columns to the right of each anchor.
            if ($nonEmptyCnt >= 1) {
                $sparse = ($total === 0) || (($nonEmptyCnt / $total) <= 0.30);
                if ($prevWasBlank || $sparse) {
                    $out[(int) $idx] = $nonEmpty;
                }
            }

            $prevWasBlank = $isAllBlank;
        }

        return $out;
    }

    private function matchesKnownVocabulary(string $value): bool
    {
        $norm = $this->normalizeForKeyword($value);
        foreach (self::KNOWN_VOCABULARY as $known) {
            if ($this->normalizeForKeyword($known) === $norm) {
                return true;
            }
        }
        return false;
    }

    /**
     * If $value matches the vocabulary, return the canonical-cased form;
     * otherwise return $value as-is. Lets the sheet contain 'battery' /
     * 'BATTERY' / 'Battery' and the resolver always see "Battery".
     */
    private function canonicalise(string $value): string
    {
        $norm = $this->normalizeForKeyword($value);
        foreach (self::KNOWN_VOCABULARY as $known) {
            if ($this->normalizeForKeyword($known) === $norm) {
                return $known;
            }
        }
        return $value;
    }

    private function normalizeForKeyword(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]/i', '', $value) ?? '');
    }
}
