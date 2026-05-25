<x-filament-panels::page>
    {{-- ────────────────────────────────────────────────────────────
        Phase 4.3.2 — operator-friendly pricing matrix import UI.

        Five visible states driven by $state:
          upload      → file picker
          analyzing   → spinner "Reading your file..."
          preview     → 4 cards + service-matching table + import button
          importing   → spinner "Importing N prices..."
          success     → green checkmark + stats grid

        Plain-English vocabulary throughout. Translation lives in
        $this->confidenceLabel() / confidenceColor() on the Page class.
        ──────────────────────────────────────────────────────────── --}}

    {{-- ───── STATE: UPLOAD ───── --}}
    @if ($state === \App\Filament\Pages\PricingMatrixImportPage::STATE_UPLOAD)
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-6 space-y-4">
            <div>
                <h2 class="text-lg font-semibold">Upload your pricing matrix</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                    Drop in the Excel file you use to manage prices — one row per vehicle, one column per service.
                    We'll read it and show you a preview before anything saves.
                </p>
            </div>

            <form wire:submit.prevent="analyze">
                {{ $this->form }}

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    {{-- Phase 4.3.3 — wire:loading toggles the inline label
                         so operator sees immediate feedback before the
                         Livewire round-trip resolves and we transition
                         into STATE_ANALYZING. --}}
                    <x-filament::button
                        type="submit"
                        icon="heroicon-o-magnifying-glass"
                        wire:loading.attr="disabled"
                        wire:target="analyze"
                    >
                        <span wire:loading.remove wire:target="analyze">Analyze file</span>
                        <span wire:loading wire:target="analyze">Analyzing…</span>
                    </x-filament::button>
                </div>
            </form>

            <div class="text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-4">
                <strong>Tip:</strong> Use <em>Download current prices</em> in the top-right to export today's matrix.
                Edit it in Excel, then upload the edited file here to update prices in bulk.
            </div>
        </div>
    @endif

    {{-- ───── STATE: ANALYZING ───── --}}
    @if ($state === \App\Filament\Pages\PricingMatrixImportPage::STATE_ANALYZING)
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent"></div>
            <p class="mt-4 text-base font-semibold">Reading your file…</p>
            <p class="mt-1 text-sm text-gray-500">Usually takes 2–5 seconds.</p>
        </div>
    @endif

    {{-- ───── STATE: PREVIEW ───── --}}
    @if ($state === \App\Filament\Pages\PricingMatrixImportPage::STATE_PREVIEW && $preview)
        @php
            $row = $preview['row_summary'] ?? [];
            $price = $preview['price_summary'] ?? [];
            $matchings = $preview['column_mappings'] ?? [];

            $matchCounts = ['exact' => 0, 'alias' => 0, 'fuzzy' => 0, 'ignored' => 0, 'unmapped' => 0];
            foreach ($matchings as $m) {
                $c = $m['confidence'] ?? 'unmapped';
                if (!isset($matchCounts[$c])) $matchCounts[$c] = 0;
                $matchCounts[$c]++;
            }
            $needsAttention = $matchCounts['unmapped'];
        @endphp

        <div class="space-y-6">
            {{-- Heading + actions bar --}}
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h2 class="text-lg font-semibold">Preview ready — review before importing</h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        We've read your file. Check the matches below, then import when you're happy.
                    </p>
                </div>
                <div class="flex gap-2">
                    <x-filament::button color="gray" wire:click="cancel" icon="heroicon-o-x-mark">
                        Cancel
                    </x-filament::button>
                    <x-filament::button
                        color="primary"
                        wire:click="commit"
                        icon="heroicon-o-check"
                        wire:confirm="Import these prices now? This will add and update rows in your live pricing table."
                        wire:loading.attr="disabled"
                        wire:target="commit"
                    >
                        <span wire:loading.remove wire:target="commit">
                            Import {{ $price['valid_prices'] ?? 0 }} prices
                        </span>
                        <span wire:loading wire:target="commit">Importing…</span>
                    </x-filament::button>
                </div>
            </div>

            {{-- ───── PHASE 4.3.5 BOOTSTRAP SUMMARY (only when something would be created) ───── --}}
            @if ($bootstrap && ($bootstrap['totalNewEntities'] ?? 0) > 0)
                @php
                    $bs = $bootstrap;
                    $brandsBs   = $bs['brands']     ?? ['matchedExisting' => 0, 'wouldCreate' => 0, 'created' => 0, 'previewNames' => []];
                    $modelsBs   = $bs['models']     ?? ['matchedExisting' => 0, 'wouldCreate' => 0, 'created' => 0, 'previewNames' => []];
                    $fuelsBs    = $bs['fuelTypes']  ?? ['matchedExisting' => 0, 'wouldCreate' => 0, 'created' => 0, 'previewNames' => []];
                    $servicesBs = $bs['services']   ?? ['matchedExisting' => 0, 'wouldCreate' => 0, 'created' => 0, 'previewNames' => []];
                    $catsBs     = $bs['categories'] ?? ['matchedExisting' => 0, 'wouldCreate' => 0, 'created' => 0, 'previewNames' => []];
                    $newKey     = ($bs['isDryRun'] ?? true) ? 'wouldCreate' : 'created';
                @endphp
                <div class="rounded-xl bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-900 p-4">
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2 flex items-center gap-2">
                        <x-heroicon-o-sparkles class="w-4 h-4" />
                        Auto-bootstrap will create:
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-xs">
                        @foreach ([
                            ['label' => 'Brands',     'row' => $brandsBs],
                            ['label' => 'Models',     'row' => $modelsBs],
                            ['label' => 'Fuel types', 'row' => $fuelsBs],
                            ['label' => 'Services',   'row' => $servicesBs],
                            ['label' => 'Categories', 'row' => $catsBs],
                        ] as $cell)
                            <div class="bg-white/60 dark:bg-blue-900/40 rounded-lg px-3 py-2">
                                <div class="text-blue-700 dark:text-blue-300 uppercase tracking-wide font-semibold text-[10px]">{{ $cell['label'] }}</div>
                                <div class="mt-1 text-blue-900 dark:text-blue-100">
                                    <span class="font-semibold">{{ $cell['row']['matchedExisting'] }}</span> existing
                                    @if (($cell['row'][$newKey] ?? 0) > 0)
                                        · <span class="font-semibold text-emerald-700 dark:text-emerald-300">{{ $cell['row'][$newKey] }} new</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ((($brandsBs['previewNames'] ?? []) || ($modelsBs['previewNames'] ?? []) || ($fuelsBs['previewNames'] ?? []) || ($servicesBs['previewNames'] ?? []) || ($catsBs['previewNames'] ?? [])))
                        <details class="mt-3 text-xs">
                            <summary class="cursor-pointer text-blue-700 dark:text-blue-300">View details</summary>
                            <div class="mt-2 space-y-2 text-blue-900 dark:text-blue-100">
                                @foreach ([
                                    ['label' => 'New brands',     'list' => $brandsBs['previewNames']   ?? []],
                                    ['label' => 'New models',     'list' => $modelsBs['previewNames']   ?? []],
                                    ['label' => 'New fuel types', 'list' => $fuelsBs['previewNames']    ?? []],
                                    ['label' => 'New services',   'list' => $servicesBs['previewNames'] ?? []],
                                    ['label' => 'New categories', 'list' => $catsBs['previewNames']     ?? []],
                                ] as $group)
                                    @if (! empty($group['list']))
                                        <div>
                                            <span class="font-semibold">{{ $group['label'] }}:</span>
                                            <span>{{ implode(', ', $group['list']) }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </details>
                    @endif

                    <div class="mt-2 text-[11px] text-blue-700 dark:text-blue-300">
                        Nothing is saved until you click <strong>Import</strong>. Cancel to back out without side-effects.
                    </div>
                </div>
            @endif

            {{-- ───── 4-CARD SUMMARY ───── --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">

                {{-- 1. File summary --}}
                <div class="rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">File</div>
                    <div class="mt-2 text-2xl font-bold">{{ $row['total'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500">vehicle rows read</div>
                </div>

                {{-- 2. Vehicles --}}
                <div class="rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Vehicles</div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $row['valid_vehicles'] ?? 0 }}</div>
                        <div class="text-xs text-gray-500">ready</div>
                    </div>
                    @if (($row['invalid_vehicles'] ?? 0) > 0)
                        <div class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $row['invalid_vehicles'] }} need attention
                        </div>
                    @endif
                </div>

                {{-- 3. Prices --}}
                <div class="rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Prices to save</div>
                    <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ $price['valid_prices'] ?? 0 }}
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ $price['will_insert'] ?? 0 }} new · {{ $price['will_update'] ?? 0 }} updates
                    </div>
                </div>

                {{-- 4. Service matching --}}
                <div class="rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Service matching</div>
                    <div class="mt-2 text-2xl font-bold">
                        {{ count($matchings) }}
                    </div>
                    <div class="text-xs">
                        @if ($needsAttention === 0)
                            <span class="text-green-600 dark:text-green-400 font-semibold">All columns matched</span>
                        @else
                            <span class="text-red-600 dark:text-red-400 font-semibold">
                                {{ $needsAttention }} need attention
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ───── SERVICE MATCHING DETAIL ───── --}}
            <details class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"
                @if($needsAttention > 0) open @endif>
                <summary class="cursor-pointer p-4 font-semibold text-sm flex items-center justify-between">
                    <span>Service matching — {{ count($matchings) }} columns</span>
                    <span class="text-xs text-gray-500">click to expand</span>
                </summary>
                <div class="border-t border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="text-xs w-full">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="text-left p-3 font-semibold">Your column</th>
                                    <th class="text-left p-3 font-semibold">Status</th>
                                    <th class="text-left p-3 font-semibold">Service it maps to</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($matchings as $m)
                                    @php
                                        $label = $this->confidenceLabel($m['confidence']);
                                        $color = $this->confidenceColor($m['confidence']);
                                        $colorClass = match($color) {
                                            'success' => 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200',
                                            'info'    => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
                                            'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
                                            'gray'    => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                            default   => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-200',
                                        };
                                        $excelKey = $m['excel'];
                                    @endphp
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td class="p-3 font-mono whitespace-nowrap">{{ $excelKey }}</td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $colorClass }}">
                                                {{ $label }}
                                            </span>
                                            @if ($m['suggestion'])
                                                <div class="text-xs text-gray-500 mt-1">{{ $m['suggestion'] }}</div>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            <select
                                                wire:model.live="columnMappingOverrides.{{ $excelKey }}"
                                                class="text-xs rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1 max-w-xs"
                                            >
                                                <option value="">— Skip this column —</option>
                                                @foreach ($this->serviceOptions as $sid => $sname)
                                                    <option value="{{ $sid }}">{{ $sname }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" wire:model.live="saveMappings" class="rounded border-gray-300 dark:border-gray-600">
                            <span><strong>Save these matches for next time</strong> — when you upload another file, we'll remember your choices.</span>
                        </label>
                    </div>
                </div>
            </details>

            {{-- ───── ISSUES (collapsible if any) ───── --}}
            @if (! empty($row['errors']))
                <details class="rounded-xl bg-white dark:bg-gray-900 border border-red-200 dark:border-red-900">
                    <summary class="cursor-pointer p-4 font-semibold text-sm text-red-700 dark:text-red-300 flex items-center justify-between">
                        <span>Rows needing attention — {{ count($row['errors']) }}</span>
                        <span class="text-xs">click to expand</span>
                    </summary>
                    <div class="p-4 border-t border-red-200 dark:border-red-900 text-xs">
                        <ul class="space-y-1.5">
                            @foreach ($row['errors'] as $err)
                                <li>
                                    <strong>Row {{ $err['row'] }}:</strong>
                                    <span class="text-gray-700 dark:text-gray-300">{{ implode('; ', $err['errors']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-3 text-gray-500 italic">
                            These rows will be skipped during import. The valid rows above will still be saved.
                        </div>
                    </div>
                </details>
            @endif

            @if (($price['invalid_prices'] ?? 0) > 0 || ($price['skipped_na'] ?? 0) > 0)
                <div class="rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 text-xs space-y-2">
                    <div class="font-semibold text-sm text-gray-700 dark:text-gray-200">Cell-level notes</div>
                    @if (($price['skipped_na'] ?? 0) > 0)
                        <div class="text-gray-600 dark:text-gray-400">
                            <strong>{{ $price['skipped_na'] }}</strong> cells are blank or marked NA — these are skipped (no change to existing prices).
                        </div>
                    @endif
                    @if (($price['invalid_prices'] ?? 0) > 0)
                        <div class="text-red-600 dark:text-red-400">
                            <strong>{{ $price['invalid_prices'] }}</strong> cells have values that aren't valid prices (e.g. text, negative numbers).
                            These will be skipped.
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- ───── STATE: IMPORTING ───── --}}
    @if ($state === \App\Filament\Pages\PricingMatrixImportPage::STATE_IMPORTING)
        @php
            $count = $preview['price_summary']['valid_prices'] ?? 0;
        @endphp
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent"></div>
            <p class="mt-4 text-base font-semibold">Importing {{ number_format($count) }} prices…</p>
            <p class="mt-1 text-sm text-gray-500">Please don't close this page.</p>
        </div>
    @endif

    {{-- ───── STATE: SUCCESS ───── --}}
    @if ($state === \App\Filament\Pages\PricingMatrixImportPage::STATE_SUCCESS && $result)
        @php
            $hasIssues = ($result['invalid'] ?? 0) > 0 || ($result['skipped'] ?? 0) > 0;
            $totalDone = $result['totalDone'] ?? 0;
        @endphp

        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-8 text-center space-y-4">
            @if ($hasIssues)
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900 mx-auto">
                    <x-heroicon-o-exclamation-triangle class="w-10 h-10 text-amber-600 dark:text-amber-300" />
                </div>
                <h2 class="text-xl font-bold">Import partially completed</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ number_format($totalDone) }} prices saved. Some cells were skipped — see counts below.
                </p>
            @else
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900 mx-auto">
                    <x-heroicon-o-check-circle class="w-10 h-10 text-green-600 dark:text-green-300" />
                </div>
                <h2 class="text-xl font-bold">Import complete</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    All {{ number_format($totalDone) }} prices saved successfully.
                </p>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6 text-left">
                <div class="bg-emerald-50 dark:bg-emerald-950 rounded-lg p-3">
                    <div class="text-xs text-emerald-700 dark:text-emerald-300 font-semibold">New prices</div>
                    <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($result['inserted'] ?? 0) }}</div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-950 rounded-lg p-3">
                    <div class="text-xs text-blue-700 dark:text-blue-300 font-semibold">Updated prices</div>
                    <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($result['updated'] ?? 0) }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                    <div class="text-xs text-gray-700 dark:text-gray-300 font-semibold">Skipped (blank/NA)</div>
                    <div class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ number_format($result['skipped'] ?? 0) }}</div>
                </div>
                <div class="bg-red-50 dark:bg-red-950 rounded-lg p-3">
                    <div class="text-xs text-red-700 dark:text-red-300 font-semibold">With issues</div>
                    <div class="text-2xl font-bold text-red-700 dark:text-red-300">{{ number_format($result['invalid'] ?? 0) }}</div>
                </div>
            </div>

            <div class="flex justify-center gap-2 pt-4">
                <x-filament::button color="gray" wire:click="importAnother" icon="heroicon-o-arrow-path">
                    Import another file
                </x-filament::button>
                <x-filament::button
                    color="primary"
                    tag="a"
                    href="{{ url('/admin/imports') }}"
                    icon="heroicon-o-list-bullet"
                >
                    View import history
                </x-filament::button>
            </div>
        </div>
    @endif

</x-filament-panels::page>
