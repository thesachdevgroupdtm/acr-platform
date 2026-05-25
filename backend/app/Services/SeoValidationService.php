<?php

namespace App\Services;

/**
 * Phase 4.5d — JSON-LD validator for the 5 schema types ACR
 * uses on its customer pages.
 *
 * The validator is intentionally pragmatic, not a full Schema.org
 * conformance check. It exists to give operators a sanity-check
 * loop inside Filament's Preview JSON-LD modal before publishing.
 *
 *   $result = app(SeoValidationService::class)->validate($jsonld);
 *   // returns ['valid' => bool, 'errors' => [], 'warnings' => [], 'info' => []]
 *
 * Coverage:
 *   - General: @context must be https://schema.org, @type required
 *   - Service: requires name + description; recommends provider
 *   - LocalBusiness: requires name, address (with streetAddress +
 *     addressLocality), telephone; recommends openingHours + geo
 *   - AutoRepair: same rules as LocalBusiness (Schema.org extends it)
 *   - FAQPage: requires mainEntity non-empty array, each entity is
 *     a Question with name + acceptedAnswer.text
 *   - Organization: requires name + url; recommends logo + sameAs
 */
class SeoValidationService
{
    /**
     * @return array{valid: bool, errors: array<string>, warnings: array<string>, info: array<string>}
     */
    public function validate(string $jsonld): array
    {
        $errors   = [];
        $warnings = [];
        $info     = [];

        $data = json_decode($jsonld, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid'    => false,
                'errors'   => ['Invalid JSON: ' . json_last_error_msg()],
                'warnings' => [],
                'info'     => [],
            ];
        }

        if (! is_array($data)) {
            return [
                'valid'    => false,
                'errors'   => ['Top-level JSON-LD must be an object.'],
                'warnings' => [],
                'info'     => [],
            ];
        }

        // ── General rules ────────────────────────────────────────
        $context = $data['@context'] ?? null;
        if ($context === null) {
            $errors[] = 'Missing @context.';
        } elseif ($context !== 'https://schema.org') {
            $errors[] = "Unexpected @context (got '{$context}'; expected 'https://schema.org').";
        }

        $type = $data['@type'] ?? null;
        if ($type === null) {
            $errors[] = 'Missing @type.';
        }

        // Per-type checks only run if we got past the basic shape.
        if (is_string($type)) {
            $info[] = "Validated against schema.org {$type} type.";
            match ($type) {
                'Service'        => $this->validateService($data, $errors, $warnings),
                'LocalBusiness',
                'AutoRepair'     => $this->validateLocalBusiness($data, $errors, $warnings),
                'FAQPage'        => $this->validateFAQPage($data, $errors, $warnings),
                'Organization'   => $this->validateOrganization($data, $errors, $warnings),
                default          => $warnings[] = "Unknown @type '{$type}' — skipped per-type validation.",
            };
        }

        return [
            'valid'    => empty($errors),
            'errors'   => $errors,
            'warnings' => $warnings,
            'info'     => $info,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    private function validateService(array $data, array &$errors, array &$warnings): void
    {
        if (empty($data['name'])) {
            $errors[] = 'Service requires a name.';
        }
        if (empty($data['description'])) {
            $errors[] = 'Service requires a description.';
        }
        if (empty($data['provider'])) {
            $warnings[] = 'Service.provider is recommended for SEO.';
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    private function validateLocalBusiness(array $data, array &$errors, array &$warnings): void
    {
        if (empty($data['name'])) {
            $errors[] = 'LocalBusiness requires a name.';
        }

        $address = $data['address'] ?? null;
        if (! is_array($address)) {
            $errors[] = 'LocalBusiness.address must be a PostalAddress object.';
        } else {
            if (empty($address['streetAddress'])) {
                $errors[] = 'LocalBusiness.address.streetAddress is required.';
            }
            if (empty($address['addressLocality'])) {
                $errors[] = 'LocalBusiness.address.addressLocality is required.';
            }
        }

        if (empty($data['telephone'])) {
            $errors[] = 'LocalBusiness requires a telephone.';
        }

        if (empty($data['openingHours']) && empty($data['openingHoursSpecification'])) {
            $warnings[] = 'LocalBusiness.openingHours (or openingHoursSpecification) is recommended.';
        }
        if (empty($data['geo'])) {
            $warnings[] = 'LocalBusiness.geo (GeoCoordinates) is recommended for map rich results.';
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    private function validateFAQPage(array $data, array &$errors, array &$warnings): void
    {
        $main = $data['mainEntity'] ?? null;
        if (! is_array($main) || $main === []) {
            $errors[] = 'FAQPage.mainEntity must be a non-empty array of Question entities.';
            return;
        }

        foreach ($main as $idx => $entity) {
            if (! is_array($entity)) {
                $errors[] = "mainEntity[{$idx}] must be an object.";
                continue;
            }
            if (($entity['@type'] ?? null) !== 'Question') {
                $errors[] = "mainEntity[{$idx}].@type must be 'Question'.";
            }
            if (empty($entity['name'])) {
                $errors[] = "mainEntity[{$idx}].name (the question text) is required.";
            }
            $answer = $entity['acceptedAnswer'] ?? null;
            if (! is_array($answer) || ($answer['@type'] ?? null) !== 'Answer' || empty($answer['text'])) {
                $errors[] = "mainEntity[{$idx}].acceptedAnswer must be an Answer with non-empty text.";
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    private function validateOrganization(array $data, array &$errors, array &$warnings): void
    {
        if (empty($data['name'])) {
            $errors[] = 'Organization requires a name.';
        }
        if (empty($data['url'])) {
            $errors[] = 'Organization requires a url.';
        }
        if (empty($data['logo'])) {
            $warnings[] = 'Organization.logo is recommended for brand rich results.';
        }
        if (empty($data['sameAs'])) {
            $warnings[] = 'Organization.sameAs (social profile URLs) is recommended.';
        }
    }
}
