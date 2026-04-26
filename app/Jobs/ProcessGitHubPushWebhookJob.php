<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\Changelog\WebhookCommitProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessGitHubPushWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /**
     * @var array<int,int>
     */
    public array $backoff = [5, 15, 30, 60];

    public function __construct(
        public int $deliveryId,
        public int $projectId,
        public int $userId,
    ) {
        $this->onConnection((string) config('changelog.queue.connection', 'redis'));
        $this->onQueue((string) config('changelog.queue.name', 'changelog'));
    }

    public function handle(WebhookCommitProcessingService $processingService): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);

        if ($delivery === null) {
            return;
        }

        $delivery->update(['status' => 'processing']);

        $processingService->processDelivery($delivery, $this->projectId, $this->userId);
    }

    public function failed(Throwable $exception): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);

        if ($delivery !== null) {
            $delivery->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);
        }

        Log::error('ProcessGitHubPushWebhookJob failed', [
            'delivery_id' => $this->deliveryId,
            'exception' => $exception,
        ]);
    }
}
