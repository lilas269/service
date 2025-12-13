<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_portfolio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('freelancer_profiles')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('project_url')->nullable();
            $table->date('completion_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_portfolio');
    }
};

