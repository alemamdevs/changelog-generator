<?php

declare(strict_types=1);

namespace App\Services\Changelog;

use App\Enums\CommitCategory;

final class CommitCategorizerService
{
    private const TYPE_MAP = [
        'feat' => CommitCategory::Feature,
        'feature' => CommitCategory::Feature,
        'fix' => CommitCategory::Fix,
        'refactor' => CommitCategory::Refactor,
        'chore' => CommitCategory::Chore,
        'docs' => CommitCategory::Docs,
        'doc' => CommitCategory::Docs,
    ];

    public function __construct(private AICommitCategoryResolver $aiFallback) {}

    /**
     * @return array{category: CommitCategory, type: string|null, scope: string|null, subject: string, body: string|null, is_breaking: bool, source: string}
     */
    public function categorize(string $message): array
    {
        $message = trim($message);
        $parts = preg_split('/\R/', $message, 2) ?: [];
        $subject = (string) ($parts[0] ?? '');
        $body = isset($parts[1]) ? trim((string) $parts[1]) : null;

        if ($subject !== '' && preg_match('/^(?<type>[a-zA-Z]+)(?:\((?<scope>[^)]+)\))?(?<breaking>!)?:\s*(?<title>.+)$/', $subject, $matches)) {
            $type = strtolower((string) $matches['type']);
            $scope = isset($matches['scope']) ? trim((string) $matches['scope']) : null;
            $title = trim((string) $matches['title']);
            $isBreaking = ! empty($matches['breaking']) || str_contains(strtoupper((string) $body), 'BREAKING CHANGE');

            if ($isBreaking) {
                return [
                    'category' => CommitCategory::Breaking,
                    'type' => $type,
                    'scope' => $scope,
                    'subject' => $title,
                    'body' => $body,
                    'is_breaking' => true,
                    'source' => 'conventional',
                ];
            }

            $category = self::TYPE_MAP[$type] ?? null;

            if ($category !== null) {
                return [
                    'category' => $category,
                    'type' => $type,
                    'scope' => $scope,
                    'subject' => $title,
                    'body' => $body,
                    'is_breaking' => false,
                    'source' => 'conventional',
                ];
            }
        }

        $aiCategory = $this->aiFallback->resolve($subject !== '' ? $subject : $message, $body);

        return [
            'category' => $aiCategory ?? CommitCategory::Chore,
            'type' => null,
            'scope' => null,
            'subject' => $subject !== '' ? $subject : $message,
            'body' => $body,
            'is_breaking' => false,
            'source' => $aiCategory === null ? 'heuristic' : 'ai',
        ];
    }
}
