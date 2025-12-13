<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->decimal('budget', 12, 2);
            $table->integer('duration_days')->nullable();
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])->default('open');
            $table->unsignedBigInteger('accepted_offer_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

