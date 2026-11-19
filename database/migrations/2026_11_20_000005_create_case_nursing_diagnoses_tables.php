<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_nursing_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('surgery_cases')->restrictOnDelete();
            $table->foreignId('nursing_diagnosis_id')->nullable()->constrained('nursing_diagnoses')->nullOnDelete();
            $table->string('phase');
            $table->string('custom_text')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_id', 'phase']);
        });

        Schema::create('case_intervention_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_nursing_diagnosis_id')->constrained('case_nursing_diagnoses')->cascadeOnDelete();
            $table->foreignId('siki_item_id')->constrained('siki_items')->cascadeOnDelete();
            $table->boolean('is_done')->default(false);
            $table->dateTime('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('case_outcome_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_nursing_diagnosis_id')->constrained('case_nursing_diagnoses')->cascadeOnDelete();
            $table->foreignId('slki_criterion_id')->constrained('slki_criteria')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(5);
            $table->dateTime('scored_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_outcome_scores');
        Schema::dropIfExists('case_intervention_items');
        Schema::dropIfExists('case_nursing_diagnoses');
    }
};
