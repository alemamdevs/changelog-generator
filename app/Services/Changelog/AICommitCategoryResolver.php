<?php

declare(strict_types=1);

namespace App\Services\Changelog;

use App\Enums\CommitCategory;
use App\Services\AI\IAIService;
use Throwable;

final class AICommitCategoryResolver
{
    public function __construct(private IAIService $aiService) {}

    public function resolve(string $subject, ?string $body = null): ?CommitCategory
    {
        $message = trim($subject."\n\n".(string) $body);

        try {
            $response = $this->aiService->generateChangelog([
                ['message' => $message],
            ]);

            $sectionKind = strtolower((string) data_get($response->getStructuredJson(), 'sections.0.kind', ''));

            if ($sectionKind === '') {
                return null;
            }

            return match (true) {
                str_contains($sectionKind, 'breaking') => CommitCategory::Breaking,
                str_contains($sectionKind, 'feature') => CommitCategory::Feature,
                str_contains($sectionKind, 'fix') => CommitCategory::Fix,
                str_contains($sectionKind, 'refactor') => CommitCategory::Refactor,
                str_contains($sectionKind, 'doc') => CommitCategory::Docs,
                str_contains($sectionKind, 'chore') => CommitCategory::Chore,
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }
}
