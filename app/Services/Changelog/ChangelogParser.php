<?php

declare(strict_types=1);

namespace App\Services\Changelog;

/**
 * Minimal changelog parser stub. Accepts raw AI output and returns an object
 * with a `sections` array. Each section is an object with `kind`, `description`,
 * and optional `details_markdown`.
 */
final class ChangelogParser
{
    /**
     * Parse AI output into a simple DTO-like object.
     *
     * @param string|mixed $raw
     * @return object
     */
    public function parse(mixed $raw): object
    {
        // Very small stub: if $raw is already an array/object with sections, normalize it.
        if (is_object($raw) && property_exists($raw, 'sections')) {
            return $raw;
        }

        if (is_array($raw) && isset($raw['sections'])) {
            return (object) $raw;
        }

        // Fallback: return a single section with raw text
        return (object) ['sections' => [ (object) ['kind' => 'Uncategorized', 'description' => is_string($raw) ? $raw : json_encode($raw), 'details_markdown' => null] ]];
    }
}
