<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureUsersTableExists();

        $ownerUserId = $this->ensureOwnerUser();

        $this->addUserColumns();
        $this->backfillUserOwnership($ownerUserId);
        $this->addForeignKeysAndIndexes();
        $this->adjustUniqueConstraints();
    }

    public function down(): void
    {
        Schema::table('changelogs', function (Blueprint $table): void {
            $this->dropForeignIfExists('changelogs', 'changelogs_user_id_foreign');
            $this->dropIndexIfExists('changelogs', 'changelogs_user_id_index');
            $this->dropIndexIfExists('changelogs', 'changelogs_user_id_release_id_index');

            if (Schema::hasColumn('changelogs', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });

        Schema::table('commits', function (Blueprint $table): void {
            $this->dropForeignIfExists('commits', 'commits_user_id_foreign');
            $this->dropIndexIfExists('commits', 'commits_user_id_index');
            $this->dropIndexIfExists('commits', 'commits_user_id_release_id_index');
            $this->dropIndexIfExists('commits', 'commits_user_id_repository_full_name_index');

            if (Schema::hasColumn('commits', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });

        Schema::table('releases', function (Blueprint $table): void {
            $this->dropForeignIfExists('releases', 'releases_user_id_foreign');
            $this->dropIndexIfExists('releases', 'releases_user_id_index');
            $this->dropIndexIfExists('releases', 'releases_user_id_repository_full_name_generated_at_index');

            $this->dropUniqueIfExists('releases', 'releases_user_id_repository_full_name_version_unique');

            if (Schema::hasColumn('releases', 'user_id')) {
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('releases', 'repository_full_name')) {
                $table->unique(['repository_full_name', 'version']);
            }
        });

        Schema::table('projects', function (Blueprint $table): void {
            $this->dropForeignIfExists('projects', 'projects_user_id_foreign');
            $this->dropIndexIfExists('projects', 'projects_user_id_index');
            $this->dropIndexIfExists('projects', 'projects_user_id_is_active_index');

            $this->dropUniqueIfExists('projects', 'projects_user_id_repository_full_name_unique');

            if (Schema::hasColumn('projects', 'user_id')) {
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('projects', 'repository_full_name')) {
                $table->unique('repository_full_name');
            }
        });
    }

    private function ensureUsersTableExists(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    private function ensureOwnerUser(): int
    {
        $existingUserId = DB::table('users')->orderBy('id')->value('id');

        if ($existingUserId !== null) {
            return (int) $existingUserId;
        }

        return (int) DB::table('users')->insertGetId([
            'name' => 'System Owner',
            'email' => 'system-owner+'.Str::lower(Str::random(10)).'@example.local',
            'password' => Hash::make(Str::random(40)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addUserColumns(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
        });

        Schema::table('releases', function (Blueprint $table): void {
            if (! Schema::hasColumn('releases', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
        });

        Schema::table('commits', function (Blueprint $table): void {
            if (! Schema::hasColumn('commits', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
        });

        Schema::table('changelogs', function (Blueprint $table): void {
            if (! Schema::hasColumn('changelogs', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
        });
    }

    private function backfillUserOwnership(int $ownerUserId): void
    {
        DB::table('projects')->whereNull('user_id')->update(['user_id' => $ownerUserId]);

        $projectOwnerByRepository = DB::table('projects')
            ->whereNotNull('repository_full_name')
            ->pluck('user_id', 'repository_full_name');

        DB::table('releases')
            ->orderBy('id')
            ->chunkById(200, function ($releases) use ($projectOwnerByRepository, $ownerUserId): void {
                foreach ($releases as $release) {
                    $scopedUserId = $projectOwnerByRepository[$release->repository_full_name] ?? $ownerUserId;

                    DB::table('releases')
                        ->where('id', $release->id)
                        ->whereNull('user_id')
                        ->update(['user_id' => $scopedUserId]);
                }
            });

        $releaseOwnerById = DB::table('releases')->pluck('user_id', 'id');

        DB::table('commits')
            ->orderBy('id')
            ->chunkById(500, function ($commits) use ($releaseOwnerById, $ownerUserId): void {
                foreach ($commits as $commit) {
                    $scopedUserId = $releaseOwnerById[$commit->release_id] ?? $ownerUserId;

                    DB::table('commits')
                        ->where('id', $commit->id)
                        ->whereNull('user_id')
                        ->update(['user_id' => $scopedUserId]);
                }
            });

        DB::table('changelogs')
            ->orderBy('id')
            ->chunkById(500, function ($entries) use ($releaseOwnerById, $ownerUserId): void {
                foreach ($entries as $entry) {
                    $scopedUserId = $releaseOwnerById[$entry->release_id] ?? $ownerUserId;

                    DB::table('changelogs')
                        ->where('id', $entry->id)
                        ->whereNull('user_id')
                        ->update(['user_id' => $scopedUserId]);
                }
            });
    }

    private function addForeignKeysAndIndexes(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (! $this->foreignKeyExists('projects', 'projects_user_id_foreign')) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }

            if (! $this->indexExists('projects', 'projects_user_id_index')) {
                $table->index('user_id');
            }

            if (! $this->indexExists('projects', 'projects_user_id_is_active_index')) {
                $table->index(['user_id', 'is_active']);
            }
        });

        Schema::table('releases', function (Blueprint $table): void {
            if (! $this->foreignKeyExists('releases', 'releases_user_id_foreign')) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }

            if (! $this->indexExists('releases', 'releases_user_id_index')) {
                $table->index('user_id');
            }

            if (! $this->indexExists('releases', 'releases_user_id_repository_full_name_generated_at_index')) {
                $table->index(['user_id', 'repository_full_name', 'generated_at']);
            }
        });

        Schema::table('commits', function (Blueprint $table): void {
            if (! $this->foreignKeyExists('commits', 'commits_user_id_foreign')) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }

            if (! $this->indexExists('commits', 'commits_user_id_index')) {
                $table->index('user_id');
            }

            if (! $this->indexExists('commits', 'commits_user_id_release_id_index')) {
                $table->index(['user_id', 'release_id']);
            }

            if (! $this->indexExists('commits', 'commits_user_id_repository_full_name_index')) {
                $table->index(['user_id', 'repository_full_name']);
            }
        });

        Schema::table('changelogs', function (Blueprint $table): void {
            if (! $this->foreignKeyExists('changelogs', 'changelogs_user_id_foreign')) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }

            if (! $this->indexExists('changelogs', 'changelogs_user_id_index')) {
                $table->index('user_id');
            }

            if (! $this->indexExists('changelogs', 'changelogs_user_id_release_id_index')) {
                $table->index(['user_id', 'release_id']);
            }
        });
    }

    private function adjustUniqueConstraints(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $this->dropUniqueIfExists('projects', 'projects_repository_full_name_unique');

            if (! $this->indexExists('projects', 'projects_user_id_repository_full_name_unique')) {
                $table->unique(['user_id', 'repository_full_name']);
            }
        });

        Schema::table('releases', function (Blueprint $table): void {
            $this->dropUniqueIfExists('releases', 'releases_repository_full_name_version_unique');

            if (! $this->indexExists('releases', 'releases_user_id_repository_full_name_version_unique')) {
                $table->unique(['user_id', 'repository_full_name', 'version']);
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
