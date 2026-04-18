<?php

declare(strict_types=1);

namespace App\Services\Changelog;

use App\Enums\CommitCategory;
use App\Models\Release;

final class SemverVersionService
{
    /**
     * @param  array<int,array{category: CommitCategory}>  $categorizedCommits
     * @return array{version: string, major: int, minor: int, patch: int, tag_name: string}
     */
    public function nextVersion(?Release $latestRelease, array $categorizedCommits, ?string $manualVersion = null): array
    {
        if ($manualVersion !== null && $manualVersion !== '') {
            [$major, $minor, $patch] = $this->parseVersion($manualVersion);

            return [
                'version' => sprintf('v%d.%d.%d', $major, $minor, $patch),
                'major' => $major,
                'minor' => $minor,
                'patch' => $patch,
                'tag_name' => sprintf('v%d.%d.%d', $major, $minor, $patch),
            ];
        }

        [$major, $minor, $patch] = $latestRelease === null
            ? [0, 0, 0]
            : $this->parseVersion((string) $latestRelease->version);

        $hasBreaking = collect($categorizedCommits)->contains(fn (array $item): bool => $item['category'] === CommitCategory::Breaking);
        $hasFeature = collect($categorizedCommits)->contains(fn (array $item): bool => $item['category'] === CommitCategory::Feature);

        if ($hasBreaking) {
            $major++;
            $minor = 0;
            $patch = 0;
        } elseif ($hasFeature) {
            $minor++;
            $patch = 0;
        } else {
            $patch++;
        }

        $version = sprintf('v%d.%d.%d', $major, $minor, $patch);

        return [
            'version' => $version,
            'major' => $major,
            'minor' => $minor,
            'patch' => $patch,
            'tag_name' => $version,
        ];
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function parseVersion(string $version): array
    {
        $normalized = ltrim(trim($version), 'vV');

        if (! preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $normalized, $matches)) {
            return [0, 0, 0];
        }

        return [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
    }
}
