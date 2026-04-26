<?php

declare(strict_types=1);

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('releases', function (Blueprint $table): void {
            if (! Schema::hasColumn('releases', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('user_id');
            }
        });

        Schema::table('commits', function (Blueprint $table): void {
            if (! Schema::hasColumn('commits', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('user_id');
            }
        });

        Schema::table('processed_commits', function (Blueprint $table): void {
            if (! Schema::hasColumn('processed_commits', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('user_id');
            }
        });

        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            if (! Schema::hasColumn('webhook_deliveries', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('user_id');
            }
        });

        $this->backfillProjectOwnership();
        $this->addForeignKeysAndIndexes();
        $this->adjustUniqueConstraints();
    }

    public function down(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            $this->dropForeignIfExists('webhook_deliveries', 'webhook_deliveries_project_id_foreign');
            $this->dropIndexIfExists('webhook_deliveries', 'webhook_deliveries_project_id_index');
            $this->dropIndexIfExists('webhook_deliveries', 'webhook_deliveries_project_id_user_id_index');

            if (Schema::hasColumn('webhook_deliveries', 'project_id')) {
                $table->dropColumn('project_id');
            }
        });

        Schema::table('processed_commits', function (Blueprint $table): void {
            $this->dropForeignIfExists('processed_commits', 'processed_commits_project_id_foreign');
            $this->dropIndexIfExists('processed_commits', 'processed_commits_project_id_index');
            $this->dropIndexIfExists('processed_commits', 'processed_commits_project_id_repository_full_name_index');
            $this->dropUniqueIfExists('processed_commits', 'processed_commits_project_id_commit_hash_unique');

            if (Schema::hasColumn('processed_commits', 'project_id')) {
                $table->dropColumn('project_id');
            }
        });

        Schema::table('commits', function (Blueprint $table): void {
            $this->dropForeignIfExists('commits', 'commits_project_id_foreign');
            $this->dropIndexIfExists('commits', 'commits_project_id_index');
            $this->dropIndexIfExists('commits', 'commits_project_id_release_id_index');
            $this->dropUniqueIfExists('commits', 'commits_project_id_commit_hash_unique');

            if (Schema::hasColumn('commits', 'project_id')) {
                $table->dropColumn('project_id');
            }
        });

        Schema::table('releases', function (Blueprint $table): void {
            $this->dropForeignIfExists('releases', 'releases_project_id_foreign');
            $this->dropIndexIfExists('releases', 'releases_project_id_index');
            $this->dropIndexIfExists('releases', 'releases_project_id_generated_at_index');
            $this->dropUniqueIfExists('releases', 'releases_project_id_version_unique');

            if (Schema::hasColumn('releases', 'project_id')) {
                $table->dropColumn('project_id');
            }
        });
    }

    private function backfillProjectOwnership(): void
    {
        DB::table('releases')
            ->whereNull('project_id')
            ->orderBy('id')
            ->chunkById(200, function ($releases): void {
                foreach ($releases as $release) {
                    $projectId = Project::query()
                        ->where('user_id', $release->user_id)
                        ->where(function ($query) use ($release): void {
                            $query->where('github_repo', $release->repository_full_name)
                                ->orWhere('repository_full_name', $release->repository_full_name);
                        })
                        ->value('id');

                    if ($projectId === null) {
                        continue;
                    }

                    DB::table('releases')
                        ->where('id', $release->id)
                        ->update(['project_id' => $projectId]);
                }
            });

        DB::table('commits')
            ->whereNull('project_id')
            ->orderBy('id')
            ->chunkById(500, function ($commits): void {
                foreach ($commits as $commit) {
                    $projectId = DB::table('releases')
                        ->where('id', $commit->release_id)
                        ->value('project_id');

                    if ($projectId === null) {
                        continue;
                    }

                    DB::table('commits')
                        ->where('id', $commit->id)
                        ->update(['project_id' => $projectId]);
                }
            });

        DB::table('processed_commits')
            ->whereNull('project_id')
            ->orderBy('id')
            ->chunkById(500, function ($processedCommits): void {
                foreach ($processedCommits as $processedCommit) {
                    $projectId = DB::table('releases')
                        ->where('id', $processedCommit->release_id)
                        ->value('project_id');

                    if ($projectId === null) {
                        continue;
                    }

                    DB::table('processed_commits')
                        ->where('id', $processedCommit->id)
                        ->update(['project_id' => $projectId]);
                }
            });

        DB::table('webhook_deliveries')
            ->whereNull('project_id')
            ->orderBy('id')
            ->chunkById(500, function ($deliveries): void {
                foreach ($deliveries as $delivery) {
                    $projectId = null;

                    if ($delivery->release_id !== null) {
                        $projectId = DB::table('releases')
                            ->where('id', $delivery->release_id)
                            ->value('project_id');
                    }

                    if ($projectId === null) {
                        $projectId = Project::query()
                            ->where('user_id', $delivery->user_id)
                            ->where(function ($query) use ($delivery): void {
                                $query->where('github_repo', $delivery->repository_full_name)
                                    ->orWhere('repository_full_name', $delivery->repository_full_name);
                            })
                            ->value('id');
                    }

                    if ($projectId === null) {
                        continue;
                    }

                    DB::table('webhook_deliveries')
                        ->where('id', $delivery->id)
                        ->update(['project_id' => $projectId]);
                }
            });
    }

    private function addForeignKeysAndIndexes(): void
    {
        Schema::table('releases', function (Blueprint $table): void {
            if (! $this->foreignKeyExists('releases', 'releases_project_id_foreign')) {
                $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            }

            if (! $this->indexExists('releases', 'releases_project_id_index')) {
                $table->index('project_id');
            }

            if (! $this->indexExists('releases', 'releases_project_id_generated_at_index')) {
                $table->index(['project_id', 'generated_at']);
            }
        });

        Schema::table('commits', function (Blueprint $table): void {
            if (! $this->foreignKeyExists('commits', 'commits_project_id_foreign')) {
                $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            }

            if (! $this->indexExists('commits', 'commits_project_id_index')) {
                $table->index('project_id');
            }

            if (! $this->indexExists('commits', 'commits_project_id_release_id_index')) {
                $table->index(['project_id', 'release_id']);
            }

            if (! $this->indexExists('commits', 'commits_project_id_commit_hash_index')) {
                $table->index(['project_id', 'commit_hash']);
            }
        });

        Schema::table('processed_commits', function (Blueprint $table): void {
            if (! $this->foreignKeyExists('processed_commits', 'processed_commits_project_id_foreign')) {
                $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            }

            if (! $this->indexExists('processed_commits', 'processed_commits_project_id_index')) {
                $table->index('project_id');
            }

            if (! $this->indexExists('processed_commits', 'processed_commits_project_id_repository_full_name_index')) {
                $table->index(['project_id', 'repository_full_name']);
            }

            if (! $this->indexExists('processed_commits', 'processed_commits_project_id_commit_hash_unique')) {
                $table->unique(['project_id', 'commit_hash']);
            }
        });

        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            if (! $this->foreignKeyExists('webhook_deliveries', 'webhook_deliveries_project_id_foreign')) {
                $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            }

            if (! $this->indexExists('webhook_deliveries', 'webhook_deliveries_project_id_index')) {
                $table->index('project_id');
            }

            if (! $this->indexExists('webhook_deliveries', 'webhook_deliveries_project_id_user_id_index')) {
                $table->index(['project_id', 'user_id']);
            }
        });
    }

    private function adjustUniqueConstraints(): void
    {
        Schema::table('releases', function (Blueprint $table): void {
            $this->dropUniqueIfExists('releases', 'releases_user_id_repository_full_name_version_unique');
            $this->dropUniqueIfExists('releases', 'releases_repository_full_name_version_unique');

            if (! $this->indexExists('releases', 'releases_project_id_version_unique')) {
                $table->unique(['project_id', 'version']);
            }
        });

        Schema::table('commits', function (Blueprint $table): void {
            $this->dropUniqueIfExists('commits', 'commits_release_id_commit_hash_unique');

            if (! $this->indexExists('commits', 'commits_project_id_commit_hash_unique')) {
                $table->unique(['project_id', 'commit_hash']);
            }
        });

        Schema::table('processed_commits', function (Blueprint $table): void {
            $this->dropUniqueIfExists('processed_commits', 'processed_commits_user_id_repository_full_name_commit_hash_unique');
            $this->dropUniqueIfExists('processed_commits', 'processed_commits_repository_full_name_commit_hash_unique');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getConnection()->getSchemaBuilder()->getIndexes($table);

        return collect($indexes)->contains(function (array $index) use ($indexName): bool {
            return ($index['name'] ?? null) === $indexName;
        });
    }

    private function foreignKeyExists(string $table, string $foreignKeyName): bool
    {
        $foreignKeys = Schema::getConnection()->getSchemaBuilder()->getForeignKeys($table);

        return collect($foreignKeys)->contains(function (array $foreignKey) use ($foreignKeyName): bool {
            return ($foreignKey['name'] ?? null) === $foreignKeyName;
        });
    }

    private function dropUniqueIfExists(string $table, string $uniqueIndexName): void
    {
        if (! $this->indexExists($table, $uniqueIndexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($uniqueIndexName): void {
            $tableBlueprint->dropUnique($uniqueIndexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName): void {
            $tableBlueprint->dropIndex($indexName);
        });
    }

    private function dropForeignIfExists(string $table, string $foreignKeyName): void
    {
        if (! $this->foreignKeyExists($table, $foreignKeyName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($foreignKeyName): void {
            $tableBlueprint->dropForeign($foreignKeyName);
        });
    }
};
