<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('preset', 40)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('bucket', 10)->default('day');
            $table->json('sections');
            $table->string('status', 20)->default('running');
            $table->string('model')->nullable();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_evaluations');
    }
};
