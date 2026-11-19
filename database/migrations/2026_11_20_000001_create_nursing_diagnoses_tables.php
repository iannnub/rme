<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('phase');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['phase', 'is_active']);
        });

        Schema::create('slki_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nursing_diagnosis_id')->constrained('nursing_diagnoses')->cascadeOnDelete();
            $table->string('outcome_code')->nullable();
            $table->string('outcome_name');
            $table->string('expectation')->default('membaik');
            $table->string('criterion_text');
            $table->unsignedTinyInteger('target_score')->default(5);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('siki_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nursing_diagnosis_id')->constrained('nursing_diagnoses')->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('siki_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siki_intervention_id')->constrained('siki_interventions')->cascadeOnDelete();
            $table->string('section');
            $table->text('text');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siki_items');
        Schema::dropIfExists('siki_interventions');
        Schema::dropIfExists('slki_criteria');
        Schema::dropIfExists('nursing_diagnoses');
    }
};
