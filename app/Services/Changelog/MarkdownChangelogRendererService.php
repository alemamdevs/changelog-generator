<?php

declare(strict_types=1);

namespace App\Services\Changelog;

use App\Enums\CommitCategory;
use App\Models\Release;
use Illuminate\Support\Facades\Storage;

final class MarkdownChangelogRendererService
{
    /**
     * @param  array<string,array<int,array{subject:string,hash:string,scope:string|null,category:CommitCategory}>>  $groupedCommits
     * @return array{path:string,content:string}
     */
    public function renderAndStore(Release $release, array $groupedCommits): array
    {
        $lines = [
            '# Changelog '.$release->version,
            '',
            '- Repository: '.($release->repository_full_name ?? 'unknown'),
            '- Branch: '.$release->branch,
            '- Generated: '.$release->generated_at?->toIso8601String(),
            '',
        ];

        foreach (CommitCategory::cases() as $category) {
            $key = $category->value;
            $entries = $groupedCommits[$key] ?? [];

            if ($entries === []) {
                continue;
            }

            $lines[] = '## '.$category->heading();
            $lines[] = '';

            foreach ($entries as $entry) {
                $scope = $entry['scope'] !== null && $entry['scope'] !== ''
                    ? sprintf('**%s:** ', $entry['scope'])
                    : '';

                $lines[] = sprintf('- %s%s (`%s`)', $scope, $entry['subject'], substr($entry['hash'], 0, 10));
            }

            $lines[] = '';
        }

        $content = implode("\n", $lines)."\n";
        $directory = 'changelogs/'.str_replace('/', '-', (string) $release->repository_full_name);
        $path = $directory.'/CHANGELOG-'.$release->version.'.md';

        Storage::disk('local')->put($path, $content);

        return [
            'path' => $path,
            'content' => $content,
        ];
    }
}
