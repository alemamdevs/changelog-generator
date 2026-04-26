<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'webhook_secret_ciphertext')) {
                $table->text('webhook_secret_ciphertext')->nullable()->after('default_branch');
            }

            if (! Schema::hasColumn('projects', 'webhook_secret_hash')) {
                $table->string('webhook_secret_hash', 64)->nullable()->after('webhook_secret_ciphertext');
            }
        });

        DB::table('projects')
            ->whereNotNull('webhook_secret')
            ->orderBy('id')
            ->chunkById(200, function ($projects): void {
                foreach ($projects as $project) {
                    $secret = (string) $project->webhook_secret;

                    if ($secret === '') {
                        continue;
                    }

                    DB::table('projects')
                        ->where('id', $project->id)
                        ->update([
                            'webhook_secret_ciphertext' => Crypt::encryptString($secret),
                            'webhook_secret_hash' => hash('sha256', $secret),
                        ]);
                }
            });

        Schema::table('projects', function (Blueprint $table): void {
            if ($this->indexExists('projects', 'projects_webhook_secret_hash_index')) {
                return;
            }

            $table->index('webhook_secret_hash');
        });

        if (Schema::hasColumn('projects', 'webhook_secret')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropColumn('webhook_secret');
            });
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'webhook_secret')) {
                $table->string('webhook_secret', 120)->nullable()->after('default_branch');
            }
        });

        DB::table('projects')
            ->whereNotNull('webhook_secret_ciphertext')
            ->orderBy('id')
            ->chunkById(200, function ($projects): void {
                foreach ($projects as $project) {
                    $ciphertext = (string) $project->webhook_secret_ciphertext;

                    if ($ciphertext === '') {
                        continue;
                    }

                    try {
                        $secret = Crypt::decryptString($ciphertext);
                    } catch (Throwable) {
                        continue;
                    }

                    DB::table('projects')
                        ->where('id', $project->id)
                        ->update(['webhook_secret' => $secret]);
                }
            });

        Schema::table('projects', function (Blueprint $table): void {
            $this->dropIndexIfExists('projects', 'projects_webhook_secret_hash_index');

            if (Schema::hasColumn('projects', 'webhook_secret_ciphertext')) {
                $table->dropColumn('webhook_secret_ciphertext');
            }

            if (Schema::hasColumn('projects', 'webhook_secret_hash')) {
                $table->dropColumn('webhook_secret_hash');
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
