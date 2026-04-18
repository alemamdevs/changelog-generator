<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\ChangelogEntryRepositoryInterface;
use App\Repositories\ChangelogRunRepositoryInterface;
use App\Repositories\CommitRepositoryInterface;
use App\Repositories\EloquentChangelogEntryRepository;
use App\Repositories\EloquentChangelogRunRepository;
use App\Repositories\EloquentCommitRepository;
use App\Repositories\EloquentProcessedCommitRepository;
use App\Repositories\ProcessedCommitRepositoryInterface;
use App\Services\AI\AIAdapter;
use App\Services\AI\IAIService;
use App\Services\Git\GitCliService;
use App\Services\Git\IGitService;
use Illuminate\Support\ServiceProvider;

final class ChangelogServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        // Bind the Git implementation
        $this->app->bind(IGitService::class, GitCliService::class);

        // Bind AI adapter
        $this->app->bind(IAIService::class, AIAdapter::class);

        // Repositories
        $this->app->bind(ChangelogRunRepositoryInterface::class, EloquentChangelogRunRepository::class);
        $this->app->bind(ChangelogEntryRepositoryInterface::class, EloquentChangelogEntryRepository::class);
        $this->app->bind(CommitRepositoryInterface::class, EloquentCommitRepository::class);
        $this->app->bind(ProcessedCommitRepositoryInterface::class, EloquentProcessedCommitRepository::class);
    }
}
