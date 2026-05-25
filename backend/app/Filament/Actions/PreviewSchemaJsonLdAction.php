<?php

namespace App\Filament\Actions;

use App\Models\SeoMetadata;
use App\Services\SchemaTemplateEngine;
use Filament\Actions\Action;

/**
 * Phase 4.5d — reusable Filament header action that previews the
 * resolved Schema.org JSON-LD for the record currently being edited.
 *
 * Reads from the **live form state** (not the saved DB row) so an
 * operator can preview unsaved changes. Dropped onto every Edit
 * page of SEO-bearing resources (SeoPage, Service, ServiceCategory,
 * ServiceCenter).
 *
 *   protected function getHeaderActions(): array
 *   {
 *       return [
 *           PreviewSchemaJsonLdAction::make(),
 *           Actions\DeleteAction::make(),
 *       ];
 *   }
 *
 * The modal renders a <pre>-formatted JSON-LD block plus two
 * buttons:
 *   - "Copy" — copies the JSON to clipboard (via Alpine.js, which
 *     ships with Filament).
 *   - "Validate" — POSTs the JSON-LD to /api/v1/seo/validate
 *     (Phase 4.5d PART E) and renders the structured result inline.
 *
 * Implementation note: we build a synthetic SeoMetadata instance
 * from the form state rather than calling $this->record->seoMetadata.
 * That way unsaved schema_type / schema_data / custom_jsonld
 * changes are reflected immediately.
 */
class PreviewSchemaJsonLdAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'previewSchemaJsonLd';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Preview JSON-LD')
            ->icon('heroicon-o-code-bracket')
            ->color('gray')
            ->modalHeading('Schema.org JSON-LD Preview')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalWidth('4xl')
            ->modalContent(function () {
                $livewire = $this->getLivewire();
                $state    = $livewire->form->getRawState();
                $record   = $livewire->record;

                // Build a transient SeoMetadata reflecting the live
                // form values. We don't persist it — just feed it to
                // the engine so the rendered JSON-LD matches what an
                // operator would see after save.
                $seo = new SeoMetadata();
                $seo->schema_type     = $state['schema_type']     ?? 'None';
                $seo->schema_data     = $state['schema_data']     ?? [];
                $seo->custom_jsonld   = $state['custom_jsonld']   ?? null;
                $seo->meta_title      = $state['meta_title']      ?? null;
                $seo->meta_description = $state['meta_description'] ?? null;
                $seo->og_image        = $state['og_image']        ?? null;

                // Attach the morph target so templates like Service /
                // LocalBusiness / Article can read $seo->seoable.
                if ($record) {
                    $seo->setRelation('seoable', $record);
                }

                $jsonld = app(SchemaTemplateEngine::class)->generate($seo);

                $pretty = null;
                if ($jsonld !== null) {
                    $decoded = json_decode($jsonld, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $pretty = json_encode(
                            $decoded,
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                        );
                    } else {
                        $pretty = $jsonld; // raw fallback
                    }
                }

                return view('filament.actions.preview-schema-jsonld', [
                    'jsonld'     => $pretty,
                    'schemaType' => $state['schema_type'] ?? 'None',
                    'validateUrl' => rtrim((string) config('app.url'), '/') . '/api/v1/seo/validate',
                ]);
            });
    }
}
