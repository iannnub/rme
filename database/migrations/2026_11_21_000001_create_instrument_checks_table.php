<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instrument_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('surgery_cases')->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained('instruments')->restrictOnDelete();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_sterile')->default(true);
            $table->boolean('is_functional')->default(true);
            $table->boolean('is_rusty')->default(false);
            $table->text('note')->nullable();
            $table->foreignId('checked_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('checked_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_id', 'instrument_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instrument_checks');
    }
};
