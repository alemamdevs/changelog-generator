<?php

declare(strict_types=1);

use App\Enums\CommitCategory;
use App\Services\AI\AIResponse;
use App\Services\AI\IAIService;
use App\Services\Changelog\AICommitCategoryResolver;
use App\Services\Changelog\CommitCategorizerService;

it('categorizes conventional commits correctly', function (): void {
    $fakeAi = new class implements IAIService
    {
        public function generateChangelog(array $parsedCommits, array $options = []): AIResponse
        {
            return new AIResponse('', null);
        }
    };

    $categorizer = new CommitCategorizerService(new AICommitCategoryResolver($fakeAi));

    $result = $categorizer->categorize("feat(parser)!: support monorepo scopes\n\nBREAKING CHANGE: output format updated");

    expect($result['category'])->toBe(CommitCategory::Breaking)
        ->and($result['is_breaking'])->toBeTrue()
        ->and($result['scope'])->toBe('parser')
        ->and($result['subject'])->toBe('support monorepo scopes');
});

it('uses ai fallback when message is not conventional', function (): void {
    $fakeAi = new class implements IAIService
    {
        public function generateChangelog(array $parsedCommits, array $options = []): AIResponse
        {
            return new AIResponse('', [
                'sections' => [
                    ['kind' => 'Features', 'items' => ['Add smart classification']],
                ],
            ]);
        }
    };

    $categorizer = new CommitCategorizerService(new AICommitCategoryResolver($fakeAi));

    $result = $categorizer->categorize('add smart classification pipeline');

    expect($result['category'])->toBe(CommitCategory::Feature)
        ->and($result['source'])->toBe('ai');
});
