<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->integer('delivery_days');
            $table->text('cover_message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
        });

        // ربط accepted_offer_id في جدول المشاريع بعد إنشاء جدول العروض
        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('accepted_offer_id')
                ->references('id')
                ->on('offers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['accepted_offer_id']);
        });

        Schema::dropIfExists('offers');
    }
};

