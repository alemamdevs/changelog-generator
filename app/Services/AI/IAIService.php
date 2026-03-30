<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * Minimal AI service interface / stub. Real implementation will wrap the
 * Laravel AI SDK and return structured responses.
 */
final class AIResponse
{
    public string $rawText;
    public mixed $structuredJson;

    public function __construct(string $rawText = '', mixed $structuredJson = null)
    {
        $this->rawText = $rawText;
        $this->structuredJson = $structuredJson;
    }

    public function getRawText(): string
    {
        return $this->rawText;
    }

    public function getStructuredJson(): mixed
    {
        return $this->structuredJson;
    }
}

interface IAIService
{
    /**
     * Generate a changelog from the parsed commits.
     *
     * @param array<int,array<string,mixed>> $parsedCommits
     * @param array<string,mixed> $options
     * @return AIResponse
     */
    public function generateChangelog(array $parsedCommits, array $options = []): AIResponse;
}
