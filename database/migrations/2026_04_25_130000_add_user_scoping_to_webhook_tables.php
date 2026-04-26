<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            if (! Schema::hasColumn('webhook_deliveries', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
        });

        Schema::table('processed_commits', function (Blueprint $table): void {
            if (! Schema::hasColumn('processed_commits', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
        });

        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            if (! $this->foreignKeyExists('webhook_deliveries', 'webhook_deliveries_user_id_foreign')) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }

            if (! $this->indexExists('webhook_deliveries', 'webhook_deliveries_user_id_index')) {
                $table->index('user_id');
            }

            if (! $this->indexExists('webhook_deliveries', 'webhook_deliveries_user_id_repository_full_name_index')) {
                $table->index(['user_id', 'repository_full_name']);
            }
        });

        Schema::table('processed_commits', function (Blueprint $table): void {
            $this->dropUniqueIfExists('processed_commits', 'processed_commits_repository_full_name_commit_hash_unique');

            if (! $this->foreignKeyExists('processed_commits', 'processed_commits_user_id_foreign')) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }

            if (! $this->indexExists('processed_commits', 'processed_commits_user_id_index')) {
                $table->index('user_id');
            }

            if (! $this->indexExists('processed_commits', 'processed_commits_user_id_repository_full_name_index')) {
                $table->index(['user_id', 'repository_full_name']);
            }

            if (! $this->indexExists('processed_commits', 'processed_commits_user_id_repository_full_name_commit_hash_unique')) {
                $table->unique(['user_id', 'repository_full_name', 'commit_hash']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('processed_commits', function (Blueprint $table): void {
            $this->dropUniqueIfExists('processed_commits', 'processed_commits_user_id_repository_full_name_commit_hash_unique');
            $this->dropIndexIfExists('processed_commits', 'processed_commits_user_id_repository_full_name_index');
            $this->dropIndexIfExists('processed_commits', 'processed_commits_user_id_index');
            $this->dropForeignIfExists('processed_commits', 'processed_commits_user_id_foreign');

            if (! $this->indexExists('processed_commits', 'processed_commits_repository_full_name_commit_hash_unique')) {
                $table->unique(['repository_full_name', 'commit_hash']);
            }

            if (Schema::hasColumn('processed_commits', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });

        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            $this->dropIndexIfExists('webhook_deliveries', 'webhook_deliveries_user_id_repository_full_name_index');
            $this->dropIndexIfExists('webhook_deliveries', 'webhook_deliveries_user_id_index');
            $this->dropForeignIfExists('webhook_deliveries', 'webhook_deliveries_user_id_foreign');

            if (Schema::hasColumn('webhook_deliveries', 'user_id')) {
                $table->dropColumn('user_id');
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

    private function foreignKeyExists(string $table, string $foreignKeyName): bool
    {
        $foreignKeys = Schema::getConnection()->getSchemaBuilder()->getForeignKeys($table);

        return collect($foreignKeys)->contains(function (array $foreignKey) use ($foreignKeyName): bool {
            return ($foreignKey['name'] ?? null) === $foreignKeyName;
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
