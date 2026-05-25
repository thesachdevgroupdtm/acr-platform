{{--
    Phase 4.5d — Preview JSON-LD modal body.

    Three states:
      1. No JSON-LD (schema_type=None or template returned null).
      2. JSON-LD present — render pretty-printed, with Copy + Validate buttons.
      3. Validation result panel — toggled by Validate button.

    Implementation:
      * Alpine.js (bundled with Filament) drives the Copy + Validate
        client-side. No JS asset / npm install needed.
      * Validate POSTs to /api/v1/seo/validate (Phase 4.5d PART E).
--}}
<div
    x-data="{
        jsonld: @js($jsonld),
        validateUrl: @js($validateUrl),
        copied: false,
        validating: false,
        result: null,
        copy() {
            if (!this.jsonld) return;
            navigator.clipboard.writeText(this.jsonld).then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 1500);
            });
        },
        async validate() {
            if (!this.jsonld) return;
            this.validating = true;
            this.result = null;
            try {
                const r = await fetch(this.validateUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ jsonld: this.jsonld }),
                });
                this.result = await r.json();
            } catch (e) {
                this.result = { valid: false, errors: ['Network error: ' + e.message], warnings: [], info: [] };
            } finally {
                this.validating = false;
            }
        },
    }"
    class="space-y-4"
>
    <div class="text-xs text-gray-500 dark:text-gray-400">
        <span class="font-semibold">Schema type:</span>
        <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">{{ $schemaType }}</code>
        <span class="ml-2">Previewing live form state — save the record to persist these values.</span>
    </div>

    @if ($jsonld)
        <pre
            class="text-xs bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto font-mono leading-relaxed max-h-[480px]"
        >{{ $jsonld }}</pre>

        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                @click="copy()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md
                       bg-primary-600 text-white hover:bg-primary-500
                       dark:bg-primary-500 dark:hover:bg-primary-400 transition-colors"
            >
                <span x-show="!copied">Copy to clipboard</span>
                <span x-show="copied" x-cloak>Copied ✓</span>
            </button>

            <button
                type="button"
                @click="validate()"
                :disabled="validating"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md
                       border border-gray-300 dark:border-gray-600
                       text-gray-700 dark:text-gray-200
                       hover:bg-gray-50 dark:hover:bg-gray-800
                       disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
                <span x-show="!validating">Validate</span>
                <span x-show="validating" x-cloak>Validating…</span>
            </button>
        </div>

        <div x-show="result" x-cloak class="space-y-2 text-xs">
            <div
                class="px-3 py-2 rounded-md border"
                :class="result?.valid
                    ? 'bg-green-50 border-green-200 text-green-800 dark:bg-green-950 dark:border-green-800 dark:text-green-200'
                    : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950 dark:border-red-800 dark:text-red-200'"
            >
                <span class="font-semibold" x-text="result?.valid ? 'Valid' : 'Invalid'"></span>
                <span x-show="result?.errors?.length">— <span x-text="result.errors.length"></span> error(s)</span>
                <span x-show="result?.warnings?.length"> — <span x-text="result.warnings.length"></span> warning(s)</span>
            </div>

            <template x-if="result?.errors?.length">
                <ul class="space-y-1 pl-4 list-disc text-red-700 dark:text-red-300">
                    <template x-for="e in result.errors" :key="e">
                        <li x-text="e"></li>
                    </template>
                </ul>
            </template>

            <template x-if="result?.warnings?.length">
                <ul class="space-y-1 pl-4 list-disc text-amber-700 dark:text-amber-300">
                    <template x-for="w in result.warnings" :key="w">
                        <li x-text="w"></li>
                    </template>
                </ul>
            </template>

            <template x-if="result?.info?.length">
                <ul class="space-y-1 pl-4 list-disc text-gray-600 dark:text-gray-400">
                    <template x-for="i in result.info" :key="i">
                        <li x-text="i"></li>
                    </template>
                </ul>
            </template>
        </div>
    @else
        <div class="text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
            <strong>No JSON-LD will be rendered for this record.</strong>
            <p class="text-xs mt-1">
                Either <code>schema_type</code> is <code>None</code>, the chosen template
                has no data to render (e.g. an empty FAQ list), or the custom JSON-LD field
                is empty / unparseable. Pick a schema type on the
                <em>Schema.org</em> tab, then re-open this preview.
            </p>
        </div>
    @endif
</div>
