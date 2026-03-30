<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class GenerateChangelogJob
 *
 * Lightweight job that receives commits extracted from a webhook and kicks off
 * the changelog generation workflow. The heavy lifting is performed by the
 * ChangelogGenerationService (to be implemented). This job serves as the
 * integration point and is queueable.
 */
final class GenerateChangelogJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Commits payload extracted from webhook.
     *
     * @var array<int,mixed>
     */
    public array $commits;

    /**
     * Additional metadata: repository info, ref, event name, etc.
     *
     * @var array<string,mixed>
     */
    public array $meta;

    /**
     * Maximum number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int|array
     */
    public int|array $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(array $commits, array $meta = [])
    {
        $this->commits = $commits;
        $this->meta = $meta;
    }

    /**
     * Execute the job.
     *
     * Dependencies are injected by the container so implementations can be swapped.
     */
    public function handle(
        \App\Services\Changelog\ChangelogGeneratorService $generator,
        \App\Services\Git\GitCliService $gitService
    ): void {
        Log::info('GenerateChangelogJob started', ['meta' => $this->meta]);

        try {
            // 1) Collect commits (if none provided, fetch from git using meta)
            $commits = $this->commits;

            if (empty($commits)) {
                $commits = $gitService->fetchCommitsFromWebhookPayload($this->meta);
            }

            if (empty($commits)) {
                Log::warning('No commits found for changelog generation', ['meta' => $this->meta]);
                return;
            }

            // Delegate full generation and persistence to the ChangelogGeneratorService
            $release = $generator->generateFromRawCommits($commits, $this->meta);

            Log::info('GenerateChangelogJob completed successfully', ['meta' => $this->meta, 'release_id' => $release->id]);
        } catch (Throwable $e) {
            // Let the failed() handler deal with logging and persistence of errors.
            Log::error('GenerateChangelogJob encountered an exception', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('GenerateChangelogJob failed', [
            'exception' => $exception,
            'meta' => $this->meta,
        ]);

        // Optionally persist failure to a tracking model or notify via events/notifications.
        try {
            if (! empty($this->meta['release_id'])) {
                $releaseId = (int) $this->meta['release_id'];

                $release = \App\Models\Release::find($releaseId);

                if ($release !== null) {
                    $release->update(['error' => $exception->getMessage()]);
                }
            }
        } catch (Throwable $ex) {
            Log::error('Failed to record job failure on release', ['exception' => $ex]);
        }
    }
}
