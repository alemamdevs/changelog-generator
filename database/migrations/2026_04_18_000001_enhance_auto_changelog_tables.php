<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 32)->index();
            $table->string('event', 64)->index();
            $table->string('delivery_id', 128)->nullable()->index();
            $table->string('repository_full_name', 255)->index();
            $table->string('ref', 255)->nullable()->index();
            $table->boolean('signature_valid')->default(false)->index();
            $table->string('status', 32)->default('pending')->index();
            $table->json('payload');
            $table->unsignedBigInteger('release_id')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('release_id')->references('id')->on('releases')->nullOnDelete();
            $table->unique(['provider', 'delivery_id']);
        });

        Schema::create('processed_commits', function (Blueprint $table): void {
            $table->id();
            $table->string('repository_full_name', 255);
            $table->string('commit_hash', 64);
            $table->unsignedBigInteger('release_id')->nullable()->index();
            $table->timestamp('processed_at')->useCurrent()->index();
            $table->timestamps();

            $table->foreign('release_id')->references('id')->on('releases')->nullOnDelete();
            $table->unique(['repository_full_name', 'commit_hash']);
            $table->index(['repository_full_name', 'processed_at']);
        });

        Schema::table('releases', function (Blueprint $table): void {
            $table->unsignedInteger('major')->default(0)->after('version');
            $table->unsignedInteger('minor')->default(0)->after('major');
            $table->unsignedInteger('patch')->default(0)->after('minor');
            $table->string('tag_name', 64)->nullable()->index()->after('patch');
            $table->string('repository_full_name', 255)->nullable()->index()->after('branch');
            $table->string('markdown_path', 255)->nullable()->after('repository_full_name');
            $table->longText('markdown_content')->nullable()->after('markdown_path');

            $table->unique(['repository_full_name', 'version']);
        });

        Schema::table('commits', function (Blueprint $table): void {
            $table->string('repository_full_name', 255)->nullable()->after('release_id');
            $table->string('scope', 100)->nullable()->after('type');
            $table->string('category', 32)->nullable()->index()->after('scope');
            $table->boolean('is_breaking')->default(false)->index()->after('category');
            $table->string('subject', 255)->nullable()->after('is_breaking');
            $table->longText('body')->nullable()->after('subject');
            $table->string('source', 32)->default('webhook')->index()->after('body');
            $table->timestamp('committed_at')->nullable()->after('source');

            $table->index(['repository_full_name', 'commit_hash']);
            $table->index(['release_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commits', function (Blueprint $table): void {
            $table->dropIndex(['repository_full_name', 'commit_hash']);
            $table->dropIndex(['release_id', 'category']);
            $table->dropColumn([
                'repository_full_name',
                'scope',
                'category',
                'is_breaking',
                'subject',
                'body',
                'source',
                'committed_at',
            ]);
        });

        Schema::table('releases', function (Blueprint $table): void {
            $table->dropUnique(['repository_full_name', 'version']);
            $table->dropColumn([
                'major',
                'minor',
                'patch',
                'tag_name',
                'repository_full_name',
                'markdown_path',
                'markdown_content',
            ]);
        });

        Schema::dropIfExists('processed_commits');
        Schema::dropIfExists('webhook_deliveries');
    }
};
