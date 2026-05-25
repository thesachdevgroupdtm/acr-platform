<?php

use App\Services\Imports\Strategies\SectionHeaderDetector;

/**
 * Phase 4.3.5 (Sub-phase 1.2) — pure unit tests for the section-header
 * detector. Input is a fixed list-of-lists shaped like raw Excel
 * rows; output is a column-index → section-name map.
 */

beforeEach(function () {
    $this->det = new SectionHeaderDetector();
});

it('detects known vocabulary sections', function () {
    $rows = [
        // Banner row: vocab match at columns 5 + 8
        ['', '', '', '', '', 'Battery', '', '', 'Brake', '', ''],
    ];
    $map = $this->det->detect($rows);
    expect($map[5])->toBe('Battery');
    expect($map[6])->toBe('Battery');
    expect($map[7])->toBe('Battery');
    expect($map[8])->toBe('Brake');
    expect($map[9])->toBe('Brake');
    expect($map[10])->toBe('Brake');
});

it('detects single-cell rows as potential sections', function () {
    // A single non-vocab non-empty cell still qualifies as a banner via
    // the sparse-row heuristic.
    $rows = [
        ['', '', '', '', '', 'Custom Heading', '', '', '', '', ''],
    ];
    $map = $this->det->detect($rows);
    expect($map[5])->toBe('Custom Heading');
});

it('falls back to Imported Services when no sections detected (returns empty map)', function () {
    // No banner row → empty map → caller assigns FALLBACK_CATEGORY.
    $rows = [
        ['Car_id', 'Make', 'Model', 'Fuel_Type', 'Segment', 'Svc 1', 'Svc 2'],
        [1, 'Audi', 'A3', 'Petrol', 'Luxury', 1000, 2000],
    ];
    $map = $this->det->detect($rows);
    expect($map)->toBe([]);
});

it('maps columns to nearest section above (multiple banners on one row)', function () {
    $rows = [
        ['', '', '', '', '', 'Battery', '', 'Brake', 'Paint'],
    ];
    $map = $this->det->detect($rows);
    expect($map[5])->toBe('Battery');
    expect($map[6])->toBe('Battery');
    expect($map[7])->toBe('Brake');
    expect($map[8])->toBe('Paint');
});

it('handles empty/sparse rows correctly (no spurious detections)', function () {
    // Empty row → no banner. Sparse data-header keywords excluded.
    $rows = [
        [],
        ['', '', '', ''],
        ['Car_id', 'Make', 'Model', 'Fuel_Type'],
    ];
    $map = $this->det->detect($rows);
    expect($map)->toBe([]);
});
