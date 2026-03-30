<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Git\IGitService;
use App\Services\Git\GitCliService;
use App\Services\AI\IAIService;
use App\Services\AI\AIAdapter;

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
        $this->app->bind(\App\Repositories\ChangelogRunRepositoryInterface::class, \App\Repositories\EloquentChangelogRunRepository::class);
        $this->app->bind(\App\Repositories\ChangelogEntryRepositoryInterface::class, \App\Repositories\EloquentChangelogEntryRepository::class);
        $this->app->bind(\App\Repositories\CommitRepositoryInterface::class, \App\Repositories\EloquentCommitRepository::class);
    }
}
