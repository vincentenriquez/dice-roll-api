<?php

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
        Schema::create('game_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('guess')->unsigned();   // 1–6, enforced at app layer
            $table->tinyInteger('result')->unsigned();  // 1–6, server-generated
            $table->boolean('is_win');
            $table->timestamps();

            $table->index('user_id');         // spec §7.3 requires both
            $table->index('transaction_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rounds');
    }
};
