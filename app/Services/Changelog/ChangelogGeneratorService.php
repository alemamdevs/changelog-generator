<?php

declare(strict_types=1);

namespace App\Services\Changelog;

use App\Repositories\ChangelogEntryRepositoryInterface;
use App\Repositories\ChangelogRunRepositoryInterface;
use App\Repositories\CommitRepositoryInterface;
use App\Services\AI\IAIService;
use App\Services\Changelog\CommitParserService;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Models\Release;

/**
 * Service that orchestrates grouping commits, calling the AI, generating the
 * final changelog structure and persisting it to the database.
 */
final class ChangelogGeneratorService
{
    public function __construct(
        private CommitParserService $commitParser,
        private IAIService $aiService,
        private ChangelogRunRepositoryInterface $runs,
        private CommitRepositoryInterface $commitsRepo,
        private ChangelogEntryRepositoryInterface $entriesRepo,
    ) {
    }

    /**
     * Generate a changelog from raw commits and persist the Release, Commits,
     * and Changelog entries.
     *
     * @param array<int,mixed> $rawCommits
     * @param array<string,mixed> $meta Optional meta (e.g. ['version' => 'v1.2.3', 'branch' => 'main'])
    * @return Release
     *
     * @throws Throwable
     */
    public function generateFromRawCommits(array $rawCommits, array $meta = []): Release
    {
        $parsed = $this->commitParser->parseRawCommits($rawCommits);

        // Group commits by normalized type for quick local grouping (not strictly required)
        $grouped = [];

        foreach ($parsed as $p) {
            $key = $p['type'] ?? 'uncategorized';
            $grouped[$key][] = $p;
        }

        // Prepare simple messages for AI consumption (prefer description over full message)
        $commitMessages = array_map(fn (array $p) => ($p['type'] ? $p['type'] . ': ' . $p['description'] : $p['description']), $parsed);

    // Call AI to generate structured changelog (returns AIResponse DTO)
    $aiResponse = $this->aiService->generateChangelog($commitMessages, ['meta' => $meta]);

    // Prefer structured JSON from the AI response, fallback to parsing raw text
    $aiResult = $aiResponse->getStructuredJson() ?? json_decode($aiResponse->getRawText() ?? '', true) ?? [];

        // Persist everything in a transaction
        return DB::transaction(function () use ($aiResult, $parsed, $meta) {
            $version = (string) ($meta['version'] ?? ($meta['tag'] ?? 'v0.0.0'));
            $branch = (string) ($meta['branch'] ?? ($meta['ref'] ?? ''));

            $release = $this->runs->createRun([
                'version' => $version,
                'branch' => $branch,
                'generated_at' => now(),
            ]);

            // Persist commits via repository
            foreach ($parsed as $p) {
                $this->commitsRepo->createCommit([
                    'release_id' => $release->id,
                    'commit_hash' => $p['hash'] ?? null,
                    'author' => $p['author'] ?? null,
                    'message' => $p['message'] ?? null,
                    'type' => $p['type'] ?? null,
                    'authored_at' => $p['timestamp'] ?? null,
                ]);
            }

            // Persist changelog entries from AI result via repository
            $position = 0;

            $sections = $aiResult['sections'] ?? [];

            foreach ($sections as $section) {
                $kind = (string) ($section['kind'] ?? 'Uncategorized');
                $items = (array) ($section['items'] ?? []);

                foreach ($items as $item) {
                    $this->entriesRepo->createEntry([
                        'release_id' => $release->id,
                        'category' => $kind,
                        'description' => (string) $item,
                        'details' => null,
                        'position' => $position++,
                    ]);
                }
            }

            return $release;
        });
    }

    /**
     * Convenience method to generate a textual markdown changelog from the AI result.
     *
     * @param array<int,mixed> $rawCommits
     */
    public function generateMarkdown(array $rawCommits): string
    {
        $parsed = $this->commitParser->parseRawCommits($rawCommits);

        $commitMessages = array_map(fn (array $p) => ($p['type'] ? $p['type'] . ': ' . $p['description'] : $p['description']), $parsed);

        $aiResponse = $this->aiService->generateChangelog($commitMessages);
        $aiResult = $aiResponse->getStructuredJson() ?? json_decode($aiResponse->getRawText() ?? '', true) ?? [];

        $sections = $aiResult['sections'] ?? [];

        $lines = [];

        foreach ($sections as $section) {
            $kind = (string) ($section['kind'] ?? 'Uncategorized');
            $lines[] = $kind;
            foreach ((array) ($section['items'] ?? []) as $item) {
                $lines[] = '- ' . trim((string) $item);
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
