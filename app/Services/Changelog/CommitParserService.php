<?php

declare(strict_types=1);

namespace App\Services\Changelog;

/**
 * Minimal commit parser service stub. Real implementation will provide
 * sophisticated parsing (conventional commits, PR mapping, scopes, etc.).
 */
final class CommitParserService
{
    /**
     * Mapping from conventional commit prefixes to normalized types.
     *
     * @var array<string,string>
     */
    private array $typeMap = [
        'feat' => 'feature',
        'fix' => 'bug fix',
        'perf' => 'improvement',
        'docs' => 'documentation',
        'refactor' => 'refactor',
    ];

    /**
     * Normalize and categorize raw commits into a compact array structure.
     *
     * Each returned item has the shape:
     * [
     *   'hash' => string|null,
     *   'author' => string|null,
     *   'message' => string,
     *   'timestamp' => string|null,
     *   'type' => string|null, // normalized type per $typeMap or null
     *   'description' => string, // message title / description
     * ]
     *
     * @param array<int,mixed> $rawCommits
     * @return array<int,array<string,mixed>>
     */
    public function parseRawCommits(array $rawCommits): array
    {
        $parsed = [];

        foreach ($rawCommits as $c) {
            $hash = $c['id'] ?? $c['sha'] ?? null;
            $author = null;

            if (isset($c['author'])) {
                if (is_array($c['author'])) {
                    $author = $c['author']['name'] ?? ($c['author']['email'] ?? null);
                } else {
                    $author = (string) $c['author'];
                }
            }

            $rawMessage = $c['message'] ?? $c['title'] ?? '';
            $messageLine = strtok((string) $rawMessage, "\n");

            [$type, $description] = $this->extractTypeAndDescription((string) $messageLine);

            $parsed[] = [
                'hash' => $hash,
                'author' => $author,
                'message' => $rawMessage,
                'timestamp' => $c['timestamp'] ?? null,
                'type' => $type,
                'description' => $description,
            ];
        }

        return $parsed;
    }

    /**
     * Extract conventional commit type and description from a commit message line.
     *
     * @return array{0:string|null,1:string}
     */
    private function extractTypeAndDescription(string $line): array
    {
        // Match patterns like: "feat(scope): description" or "fix: message"
        if (preg_match('/^(?<type>\w+)(?:\([^)]*\))?:\s*(?<desc>.+)$/', $line, $m)) {
            $rawType = strtolower($m['type']);
            $desc = trim($m['desc']);

            $normalized = $this->typeMap[$rawType] ?? null;

            return [$normalized, $desc];
        }

        // No conventional prefix found; return null type and full line as description
        return [null, $line];
    }
}
