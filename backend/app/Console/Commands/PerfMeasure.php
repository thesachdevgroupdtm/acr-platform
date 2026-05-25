<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/**
 * Backend performance diagnostic. Dispatches each measured endpoint
 * INTERNALLY through the kernel so we get the real controller →
 * resource → response pipeline (not just raw SQL), captures every
 * query the request fires via DB::listen, and reports query count
 * + total SQL time + slowest single query.
 *
 * Temporary tooling — delete or leave in place after re-measurement.
 * Read-only: never mutates state.
 */
class PerfMeasure extends Command
{
    protected $signature = 'perf:measure {--json : Emit JSON for downstream diffing}';
    protected $description = 'Time + query-count each public endpoint with realistic params.';

    public function handle(): int
    {
        $cases = [
            ['GET /home',                                    '/api/v1/home',                                                                                  []],
            ['GET /services (no vehicle)',                   '/api/v1/services',                                                                              []],
            ['GET /services?brand_id=34&model_id=317&fuel_id=5', '/api/v1/services',                                                                          ['brand_id' => 34, 'model_id' => 317, 'fuel_id' => 5]],
            ['GET /services/{cat}',                          '/api/v1/services/car-battery',                                                                  []],
            ['GET /services/{cat} (with vehicle)',           '/api/v1/services/car-battery',                                                                  ['brand' => 'audi', 'model' => 'a3', 'fuel' => 'petrol']],
            ['GET /services/{cat}/{svc}',                    '/api/v1/services/car-battery/battery-charging',                                                 []],
            ['GET /services/{cat}/{svc} (with vehicle)',     '/api/v1/services/car-battery/battery-charging',                                                 ['brand_id' => 34, 'model_id' => 317, 'fuel_id' => 5]],
            ['GET /vehicle/brands',                          '/api/v1/vehicle/brands',                                                                        []],
            ['GET /vehicle/models?brand_id=34',              '/api/v1/vehicle/models',                                                                        ['brand_id' => 34]],
            ['GET /vehicle/fuels?brand_id=34&model_id=317',  '/api/v1/vehicle/fuels',                                                                         ['brand_id' => 34, 'model_id' => 317]],
        ];

        $rows = [];
        foreach ($cases as [$label, $path, $query]) {
            $rows[] = $this->measure($label, $path, $query);
        }

        if ($this->option('json')) {
            $this->line(json_encode($rows, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        // Render table
        $this->table(
            ['Endpoint', 'HTTP', 'Queries', 'SQL ms', 'PHP ms', 'Slowest (ms)', 'Slowest SQL (truncated)'],
            array_map(fn ($r) => [
                $r['label'],
                $r['status'],
                $r['count'],
                number_format($r['sql_ms'], 2),
                number_format($r['php_ms'], 2),
                number_format($r['slow_ms'], 2),
                substr(str_replace(["\n", "  "], [' ', ' '], $r['slow_sql'] ?? ''), 0, 90),
            ], $rows),
        );

        return self::SUCCESS;
    }

    /** @return array<string,mixed> */
    private function measure(string $label, string $path, array $query): array
    {
        $queries = [];
        $listener = function ($q) use (&$queries) {
            $queries[] = ['sql' => $q->sql, 'time' => $q->time, 'bindings' => $q->bindings];
        };

        // Two-pass: warm autoloader + opcache once, then measure on the
        // second call. Without this the first dispatch in the run
        // pays the cold-class-loading cost and reads ~3x slower.
        $this->silentDispatch($path, $query);

        DB::listen($listener);
        $start = microtime(true);
        try {
            $response = $this->silentDispatch($path, $query);
            $status = $response?->getStatusCode() ?? 0;
        } catch (\Throwable $e) {
            $status = 500;
        }
        $totalMs = (microtime(true) - $start) * 1000;
        DB::flushQueryLog();

        $sqlMs = array_sum(array_column($queries, 'time'));
        $slow  = null;
        foreach ($queries as $q) {
            if ($slow === null || $q['time'] > $slow['time']) {
                $slow = $q;
            }
        }

        return [
            'label'    => $label,
            'path'     => $path,
            'query'    => $query,
            'status'   => $status,
            'count'    => count($queries),
            'sql_ms'   => $sqlMs,
            'php_ms'   => max(0, $totalMs - $sqlMs),
            'slow_ms'  => $slow['time'] ?? 0,
            'slow_sql' => $slow['sql']  ?? null,
        ];
    }

    private function silentDispatch(string $path, array $query)
    {
        $request = Request::create($path, 'GET', $query);
        $request->headers->set('Accept', 'application/json');
        return app()->handle($request);
    }
}
