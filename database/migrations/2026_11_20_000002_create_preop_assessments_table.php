<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preop_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('surgery_cases')->restrictOnDelete();
            $table->text('chief_complaint')->nullable();
            $table->text('current_illness_history')->nullable();
            $table->text('past_illness_history')->nullable();
            $table->text('surgery_history')->nullable();
            $table->string('allergy_status')->default('belum_diketahui');
            $table->text('allergy_detail')->nullable();
            $table->text('medication_history')->nullable();
            $table->text('anesthesia_history')->nullable();
            $table->boolean('fasting_status')->default(false);
            $table->dateTime('fasting_since')->nullable();
            $table->string('skin_condition')->nullable();
            $table->string('anxiety_level')->default('tidak_cemas');
            $table->text('surgery_knowledge')->nullable();
            $table->text('family_support')->nullable();
            $table->text('mental_readiness')->nullable();
            $table->json('preparation')->nullable();
            $table->text('implementation_actions')->nullable();
            $table->text('implementation_education')->nullable();
            $table->text('implementation_monitoring')->nullable();
            $table->text('implementation_response')->nullable();
            $table->text('evaluation_condition')->nullable();
            $table->text('evaluation_completeness')->nullable();
            $table->string('readiness_status')->default('belum_siap');
            $table->text('evaluation_notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preop_assessments');
    }
};
