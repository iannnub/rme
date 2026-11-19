<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instrument_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('surgery_cases')->cascadeOnDelete();
            $table->string('item', 40);
            $table->unsignedInteger('initial_count')->default(0);
            $table->unsignedInteger('additional_count')->default(0);
            $table->unsignedInteger('final_count')->nullable();
            $table->string('status', 5)->nullable(); // S|TS
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['case_id', 'item']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instrument_counts');
    }
};
