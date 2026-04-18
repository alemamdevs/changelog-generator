<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessGitHubPushWebhookJob;
use App\Models\WebhookDelivery;
use App\Services\Changelog\WebhookCommitProcessingService;
use App\Services\Git\GitRangeReaderService;
use Illuminate\Console\Command;

final class GenerateChangelogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'changelog:generate
                            {--repo= : Local repository path}
                            {--from= : Start git ref}
                            {--to=HEAD : End git ref}
                            {--branch=main : Branch name metadata}
                            {--queue : Dispatch processing to queue instead of sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate changelog from git commit range with duplicate prevention and semver bumping.';

    public function handle(
        GitRangeReaderService $rangeReader,
        WebhookCommitProcessingService $processingService,
    ): int {
        $repo = (string) ($this->option('repo') ?: base_path());
        $from = (string) ($this->option('from') ?: $this->ask('Enter start reference (e.g. v1.2.3 or HEAD~50)'));
        $to = (string) $this->option('to');
        $branch = (string) $this->option('branch');

        if ($from === '') {
            $this->error('A start reference is required.');

            return self::FAILURE;
        }

        try {
            $commits = $rangeReader->read($repo, $from, $to);
        } catch (\Throwable $exception) {
            $this->error('Unable to read commit range: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($commits === []) {
            $this->warn('No commits found in the provided range.');

            return self::SUCCESS;
        }

        $payload = [
            'ref' => 'refs/heads/'.$branch,
            'repository' => [
                'full_name' => (string) config('changelog.default_repository', 'local/repository'),
            ],
            'commits' => array_map(fn (array $commit): array => [
                'id' => $commit['hash'],
                'message' => $commit['message'],
                'timestamp' => $commit['timestamp'],
                'author' => ['name' => $commit['author']],
            ], $commits),
        ];

        $delivery = WebhookDelivery::query()->create([
            'provider' => 'local-cli',
            'event' => 'manual.generate',
            'delivery_id' => uniqid('cli-', true),
            'repository_full_name' => (string) data_get($payload, 'repository.full_name'),
            'ref' => (string) data_get($payload, 'ref'),
            'signature_valid' => true,
            'status' => 'pending',
            'payload' => $payload,
        ]);

        if ((bool) $this->option('queue')) {
            ProcessGitHubPushWebhookJob::dispatch($delivery->id)
                ->onConnection((string) config('changelog.queue.connection', 'redis'))
                ->onQueue((string) config('changelog.queue.name', 'changelog'));

            $this->info('Changelog processing queued successfully.');

            return self::SUCCESS;
        }

        $release = $processingService->processDelivery($delivery);

        if ($release === null) {
            $this->warn('Processing completed with no new release (no new commits or duplicates).');

            return self::SUCCESS;
        }

        $this->info('Release generated: '.$release->version);
        $this->line('Markdown path: '.(string) $release->markdown_path);

        return self::SUCCESS;
    }
}
