<x-filament-panels::page>
    @php
        $tabs = [
            'brands'     => ['label' => 'Brands',     'prop' => 'brandUploads',    'hint' => 'Filename = brand name. e.g. Audi.png'],
            'models'     => ['label' => 'Models',      'prop' => 'modelUploads',    'hint' => 'Brand_Model.png (or just Model.png if unique). e.g. Audi_Q5.png'],
            'services'   => ['label' => 'Services',    'prop' => 'serviceUploads',  'hint' => 'Filename = service name. e.g. Battery Replacement.png'],
            'categories' => ['label' => 'Categories',  'prop' => 'categoryUploads', 'hint' => 'Filename = category name. e.g. Battery.png'],
            'fuel-types' => ['label' => 'Fuel Types',  'prop' => 'fuelUploads',     'hint' => 'Filename = fuel name. e.g. Petrol.png'],
        ];
    @endphp

    <div class="space-y-5">
        {{-- Intro --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-5">
            <h2 class="text-lg font-semibold">Bulk image upload</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                Pick a tab, then drop in images — by <strong>multiple file select</strong>, a whole
                <strong>folder</strong>, or a <strong>.zip</strong>. Matching + upload happen
                automatically; no extra button. Filenames match entity names (case-insensitive).
                png / jpg / jpeg / webp · max 5 MB each.
            </p>
        </div>

        {{-- Tab nav --}}
        <div class="flex flex-wrap gap-1 border-b border-gray-200 dark:border-gray-700">
            @foreach ($tabs as $key => $tab)
                <button type="button" wire:click="setActiveTab('{{ $key }}')"
                    @class([
                        'px-4 py-2 text-sm font-medium -mb-px border-b-2 transition',
                        'border-primary-600 text-primary-600' => $activeTab === $key,
                        'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' => $activeTab !== $key,
                    ])>
                    {{ $tab['label'] }}
                    @if (!empty($results[$key]['total_matched']))
                        <span class="ml-1 inline-flex items-center justify-center rounded-full bg-success-100 text-success-700 text-xs px-1.5">{{ $results[$key]['total_matched'] }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Tab panels --}}
        @foreach ($tabs as $key => $tab)
            <div @class(['space-y-4' => true, 'hidden' => $activeTab !== $key]) wire:key="tab-{{ $key }}">
                <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-5 space-y-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $tab['hint'] }}</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Method 1 + 3: multiple files OR a .zip --}}
                        <label class="flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 p-6 cursor-pointer hover:border-primary-500 transition">
                            <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-6 w-6 text-gray-400" />
                            <span class="text-sm font-medium">Select images or a .zip</span>
                            <span class="text-xs text-gray-400">multiple files supported</span>
                            <input type="file" class="hidden" multiple
                                accept="image/png,image/jpeg,image/jpg,image/webp,.zip,application/zip"
                                wire:model="{{ $tab['prop'] }}">
                        </label>

                        {{-- Method 2: a folder --}}
                        <label class="flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 p-6 cursor-pointer hover:border-primary-500 transition">
                            <x-filament::icon icon="heroicon-o-folder-open" class="h-6 w-6 text-gray-400" />
                            <span class="text-sm font-medium">Select a folder</span>
                            <span class="text-xs text-gray-400">uploads every image inside</span>
                            <input type="file" class="hidden" webkitdirectory multiple
                                wire:model="{{ $tab['prop'] }}">
                        </label>
                    </div>

                    <div wire:loading wire:target="{{ $tab['prop'] }}" class="flex items-center gap-2 text-sm text-gray-500">
                        <x-filament::loading-indicator class="h-4 w-4" /> Uploading & matching…
                    </div>
                </div>

                {{-- Result card --}}
                @if (!empty($results[$key]))
                    @php($r = $results[$key])
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-5 space-y-3 bg-white dark:bg-gray-900">
                        <p class="text-sm font-semibold">
                            ✅ {{ $r['total_matched'] }} uploaded
                            @if ($r['total_unmatched'] > 0)
                                <span class="text-warning-600">· ⚠️ {{ $r['total_unmatched'] }} not matched</span>
                            @endif
                            @if ($r['total_skipped'] > 0)
                                <span class="text-gray-500">· {{ $r['total_skipped'] }} skipped</span>
                            @endif
                        </p>

                        @if ($r['total_unmatched'] > 0)
                            <div class="text-xs">
                                <span class="font-medium text-warning-700 dark:text-warning-300">Not matched (fix the filename to the entity name):</span>
                                <span class="font-mono text-gray-600 dark:text-gray-300">{{ implode(', ', $r['unmatched'][$key] ?? []) }}</span>
                            </div>
                        @endif

                        @if ($r['total_skipped'] > 0)
                            <ul class="text-xs space-y-1">
                                @foreach ($r['skipped'] as $s)
                                    <li><span class="font-mono">{{ $s['filename'] }}</span> — <span class="text-gray-500">{{ $s['reason'] }}</span></li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($r['total_matched'] > 0)
                            <details class="text-xs">
                                <summary class="cursor-pointer text-success-700 dark:text-success-300">View {{ $r['total_matched'] }} matched ▼</summary>
                                <ul class="mt-2 space-y-1">
                                    @foreach (($r['matched'][$key] ?? []) as $m)
                                        <li><span class="font-mono">{{ $m['filename'] }}</span> → {{ $m['entity'] }} <span class="text-gray-400">({{ $m['stored_path'] }})</span></li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
