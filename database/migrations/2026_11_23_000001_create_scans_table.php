<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table): void {
            $table->id();
            $table->string('owner_token', 32)->index();
            $table->string('input', 2048);
            $table->string('host', 253)->index();
            $table->string('url', 2048);
            $table->unsignedTinyInteger('overall_score')->default(0);
            $table->string('grade', 1)->default('F');
            $table->json('section_scores');
            $table->json('report');
            $table->string('locale', 5)->default('ar');
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->index(['owner_token', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
