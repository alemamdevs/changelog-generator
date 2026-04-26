<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('releases', function (Blueprint $table): void {
            if (! $this->indexExists('releases', 'releases_user_id_project_id_index')) {
                $table->index(['user_id', 'project_id']);
            }
        });

        Schema::table('commits', function (Blueprint $table): void {
            if (! $this->indexExists('commits', 'commits_user_id_project_id_index')) {
                $table->index(['user_id', 'project_id']);
            }
        });

        Schema::table('processed_commits', function (Blueprint $table): void {
            if (! $this->indexExists('processed_commits', 'processed_commits_user_id_project_id_index')) {
                $table->index(['user_id', 'project_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('processed_commits', function (Blueprint $table): void {
            $this->dropIndexIfExists('processed_commits', 'processed_commits_user_id_project_id_index');
        });

        Schema::table('commits', function (Blueprint $table): void {
            $this->dropIndexIfExists('commits', 'commits_user_id_project_id_index');
        });

        Schema::table('releases', function (Blueprint $table): void {
            $this->dropIndexIfExists('releases', 'releases_user_id_project_id_index');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getConnection()->getSchemaBuilder()->getIndexes($table);

        return collect($indexes)->contains(function (array $index) use ($indexName): bool {
            return ($index['name'] ?? null) === $indexName;
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
