<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Agents\ChangelogAgent;
use Illuminate\Support\Facades\Log;

/**
 * Service that generates a human-readable changelog from a list of commits
 * using the Laravel AI SDK.
 */
final class AIChangelogService
{
    /**
     * Generate a structured changelog from commit messages.
     *
     * @param array<int,string> $commits List of commit messages (simple strings) or commit objects.
     * @return array{sections: array<int,array{kind:string,items:array<int,string>}>}
     */
    public function generateFromCommits(array $commits): array
    {
        // Build a compact prompt that lists commits and asks for JSON output.
        $prompt = $this->buildPrompt($commits);

        try {
            $agent = ChangelogAgent::make();

            $response = $agent->prompt($prompt);

            $text = (string) $response;

            // Try to decode JSON first
            $decoded = json_decode($text, true);

            if (is_array($decoded) && isset($decoded['sections']) && is_array($decoded['sections'])) {
                // Normalize sections to expected shape
                $sections = [];

                foreach ($decoded['sections'] as $s) {
                    $kind = (string) ($s['kind'] ?? ($s['title'] ?? 'Uncategorized'));
                    $items = (array) ($s['items'] ?? $s['entries'] ?? []);
                    $sections[] = ['kind' => $kind, 'items' => array_values($items)];
                }

                return ['sections' => $sections];
            }

            // Fallback: parse markdown-like output (headings + - items)
            return $this->parseMarkdownLike($text);
        } catch (\Throwable $e) {
            Log::error('AIChangelogService failed to generate changelog', ['exception' => $e]);

            // As a last resort, produce a very simple changelog using conventional commit prefixes.
            return $this->fallbackFromConventionalCommits($commits);
        }
    }

    /**
     * Build the prompt sent to the AI.
     *
     * @param array<int,string> $commits
     */
    private function buildPrompt(array $commits): string
    {
        $exampleIn = "fix: cart coupon bug\nfeat: bulk discount";
        $exampleOut = <<<'JSON'
{"sections":[{"kind":"Bug Fixes","items":["Fixed cart coupon issue"]},{"kind":"Features","items":["Added bulk discount support"]}]}
JSON;

        $commitLines = array_map(fn ($c) => is_array($c) && isset($c['message']) ? $c['message'] : (string) $c, $commits);

        $body = "Convert the following git commit messages into a human-readable changelog grouped by type. Return only valid JSON with a top-level 'sections' array where each section has 'kind' and 'items' (an array of strings).\n\nExamples:\nInput:\n" . $exampleIn . "\nOutput:\n" . $exampleOut . "\n\nNow convert these commits:\n" . implode("\n", $commitLines) . "\n";

        return $body;
    }

    /**
     * Very small parser for markdown-like AI output.
     */
    private function parseMarkdownLike(string $text): array
    {
        $lines = preg_split('/\r?\n/', $text) ?: [];

        $sections = [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // Heading detection (e.g., "Bug Fixes" or "## Bug Fixes")
            if (preg_match('/^#{1,6}\s*(.+)$/', $line, $m) || (!str_starts_with($line, '-') && preg_match('/^[A-Z][A-Za-z0-9 ]+$/', $line))) {
                $heading = $m[1] ?? $line;
                $current = ['kind' => trim($heading), 'items' => []];
                $sections[] = &$current;
                continue;
            }

            // List item
            if (str_starts_with($line, '-')) {
                $item = trim(ltrim($line, '- '));
                if ($current === null) {
                    $current = ['kind' => 'Uncategorized', 'items' => []];
                    $sections[] = &$current;
                }

                $current['items'][] = $item;
                continue;
            }

            // Fallback: treat as item
            if ($current === null) {
                $current = ['kind' => 'Uncategorized', 'items' => []];
                $sections[] = &$current;
            }

            $current['items'][] = $line;
        }

        // Ensure sections are normalized
        $normalized = array_map(fn ($s) => ['kind' => $s['kind'] ?? 'Uncategorized', 'items' => array_values($s['items'] ?? [])], $sections);

        return ['sections' => $normalized];
    }

    /**
     * Simple fallback using conventional commit prefixes.
     *
     * @param array<int,mixed> $commits
     */
    private function fallbackFromConventionalCommits(array $commits): array
    {
        $map = [
            'fix' => 'Bug Fixes',
            'feat' => 'Features',
            'perf' => 'Performance Improvements',
            'docs' => 'Documentation',
            'chore' => 'Chores',
            'refactor' => 'Refactors',
        ];

        $sections = [];

        foreach ($commits as $c) {
            $message = is_array($c) && isset($c['message']) ? $c['message'] : (string) $c;

            if (preg_match('/^(\w+)(?:\(.+\))?:\s*(.+)$/', $message, $m)) {
                $type = strtolower($m[1]);
                $desc = $m[2];
                $kind = $map[$type] ?? ucfirst($type);

                $found = null;
                foreach ($sections as &$s) {
                    if ($s['kind'] === $kind) {
                        $found = &$s;
                        break;
                    }
                }

                if ($found === null) {
                    $sections[] = ['kind' => $kind, 'items' => [$desc]];
                } else {
                    $found['items'][] = $desc;
                }
            } else {
                // Uncategorized
                if (empty($sections) || $sections[count($sections) - 1]['kind'] !== 'Uncategorized') {
                    $sections[] = ['kind' => 'Uncategorized', 'items' => []];
                }

                $sections[count($sections) - 1]['items'][] = $message;
            }
        }

        return ['sections' => $sections];
    }
}
