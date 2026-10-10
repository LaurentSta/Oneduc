<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_finder_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('formateur_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('image_path', 255);
            $table->json('zones');
            $table->timestamps();

            $table->index(['formateur_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_finder_activities');
    }
};
