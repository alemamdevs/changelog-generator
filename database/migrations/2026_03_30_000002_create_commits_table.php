<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreateCommitsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('release_id')->constrained('releases')->cascadeOnDelete();
            $table->string('commit_hash', 64);
            $table->string('author', 255)->nullable();
            $table->text('message')->nullable();
            $table->string('type', 64)->nullable();
            $table->timestamp('authored_at')->nullable();
            $table->timestamps();

            $table->index(['release_id']);
            $table->index(['commit_hash']);
            $table->index(['type']);
            $table->unique(['release_id', 'commit_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commits');
    }
}
