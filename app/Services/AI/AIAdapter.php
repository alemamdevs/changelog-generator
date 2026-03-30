<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AIResponse as AIResponseModel;
use Illuminate\Support\Facades\Log;
use App\Services\AI\IAIService;
use App\Services\AI\AIChangelogService;

/**
 * Adapter that implements the IAIService interface and delegates to the
 * AIChangelogService which uses the Laravel AI SDK.
 */
final class AIAdapter implements IAIService
{
    public function __construct(private AIChangelogService $aiChangelog)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function generateChangelog(array $parsedCommits, array $options = []): \App\Services\AI\AIResponse
    {
        try {
            $messages = array_map(fn ($p) => is_array($p) ? ($p['message'] ?? ($p['description'] ?? '')) : (string) $p, $parsedCommits);

            $result = $this->aiChangelog->generateFromCommits($messages);

            $raw = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';

            // Persist raw AI response for auditing. Non-fatal if this fails.
            try {
                AIResponseModel::create([
                    'release_id' => $options['release_id'] ?? null,
                    'raw_text' => $raw,
                    'structured_json' => $result,
                    'model' => $options['model'] ?? null,
                    'meta' => $options['meta'] ?? null,
                ]);
            } catch (\Throwable $e) {
                Log::warning('AIAdapter: failed to persist AIResponse', ['exception' => $e]);
            }

            return new \App\Services\AI\AIResponse($raw, $result);
        } catch (\Throwable $e) {
            Log::error('AIAdapter failed to generate changelog', ['exception' => $e]);

            try {
                AIResponseModel::create([
                    'release_id' => $options['release_id'] ?? null,
                    'raw_text' => (string) $e->getMessage(),
                    'structured_json' => null,
                    'model' => $options['model'] ?? null,
                    'meta' => $options['meta'] ?? null,
                ]);
            } catch (\Throwable $_) {
                // ignore
            }

            return new \App\Services\AI\AIResponse('', null);
        }
    }
}
