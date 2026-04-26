<?php

declare(strict_types=1);

use App\Models\Release;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('changelogs', function (Blueprint $table): void {
            if (! Schema::hasColumn('changelogs', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('release_id');
            }
        });

        DB::table('changelogs')
            ->whereNull('project_id')
            ->orderBy('id')
            ->chunkById(500, function ($entries): void {
                foreach ($entries as $entry) {
                    $release = Release::query()->select('id', 'project_id')->find($entry->release_id);

                    if ($release === null || $release->project_id === null) {
                        continue;
                    }

                    DB::table('changelogs')
                        ->where('id', $entry->id)
                        ->update(['project_id' => $release->project_id]);
                }
            });

        Schema::table('changelogs', function (Blueprint $table): void {
            if (! $this->foreignKeyExists('changelogs', 'changelogs_project_id_foreign')) {
                $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            }

            if (! $this->indexExists('changelogs', 'changelogs_project_id_index')) {
                $table->index('project_id');
            }

            if (! $this->indexExists('changelogs', 'changelogs_project_id_release_id_index')) {
                $table->index(['project_id', 'release_id']);
            }

            if (! $this->indexExists('changelogs', 'changelogs_project_id_user_id_index')) {
                $table->index(['project_id', 'user_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('changelogs', function (Blueprint $table): void {
            $this->dropIndexIfExists('changelogs', 'changelogs_project_id_user_id_index');
            $this->dropIndexIfExists('changelogs', 'changelogs_project_id_release_id_index');
            $this->dropIndexIfExists('changelogs', 'changelogs_project_id_index');
            $this->dropForeignIfExists('changelogs', 'changelogs_project_id_foreign');

            if (Schema::hasColumn('changelogs', 'project_id')) {
                $table->dropColumn('project_id');
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
