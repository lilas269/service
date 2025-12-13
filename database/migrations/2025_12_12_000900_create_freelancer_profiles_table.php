<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->text('skills')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_profiles');
    }
};

