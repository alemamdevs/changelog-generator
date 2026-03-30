<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('ai_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('release_id')->nullable()->index();
            $table->text('raw_text');
            $table->json('structured_json')->nullable();
            $table->string('model')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('release_id')->references('id')->on('releases')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_responses');
    }
};
