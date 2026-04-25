<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'github_repo')) {
                $table->string('github_repo', 255)->nullable()->after('name');
            }

            if (! Schema::hasColumn('projects', 'webhook_secret')) {
                $table->string('webhook_secret', 120)->nullable()->after('default_branch');
            }
        });

        DB::table('projects')
            ->whereNull('github_repo')
            ->update(['github_repo' => DB::raw('repository_full_name')]);

        DB::table('projects')
            ->whereNull('webhook_secret')
            ->orderBy('id')
            ->chunkById(200, function ($projects): void {
                foreach ($projects as $project) {
                    DB::table('projects')
                        ->where('id', $project->id)
                        ->update(['webhook_secret' => Str::random(64)]);
                }
            });

        Schema::table('projects', function (Blueprint $table): void {
            $this->dropUniqueIfExists('projects', 'projects_user_id_repository_full_name_unique');

            if (! $this->indexExists('projects', 'projects_user_id_github_repo_unique')) {
                $table->unique(['user_id', 'github_repo']);
            }

            if (! $this->indexExists('projects', 'projects_user_id_github_repo_index')) {
                $table->index(['user_id', 'github_repo']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $this->dropIndexIfExists('projects', 'projects_user_id_github_repo_index');
            $this->dropUniqueIfExists('projects', 'projects_user_id_github_repo_unique');

            if (! $this->indexExists('projects', 'projects_user_id_repository_full_name_unique')) {
                $table->unique(['user_id', 'repository_full_name']);
            }

            if (Schema::hasColumn('projects', 'webhook_secret')) {
                $table->dropColumn('webhook_secret');
            }

            if (Schema::hasColumn('projects', 'github_repo')) {
                $table->dropColumn('github_repo');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getConnection()->getSchemaBuilder()->getIndexes($table);

        return collect($indexes)->contains(function (array $index) use ($indexName): bool {
            return ($index['name'] ?? null) === $indexName;
        });
    }

    private function dropUniqueIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName): void {
            $tableBlueprint->dropUnique($indexName);
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
};
