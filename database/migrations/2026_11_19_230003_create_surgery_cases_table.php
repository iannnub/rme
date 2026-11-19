<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgery_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->string('case_number')->unique();
            $table->string('status')->default('pre_op');
            $table->dateTime('admitted_at');
            $table->string('origin_ward');
            $table->string('origin_class')->nullable();
            $table->dateTime('scheduled_at');
            $table->foreignId('medical_diagnosis_id')->nullable()->constrained('medical_diagnoses')->restrictOnDelete();
            $table->string('medical_diagnosis_other')->nullable();
            $table->foreignId('surgery_type_id')->nullable()->constrained('surgery_types')->restrictOnDelete();
            $table->string('surgery_type_other')->nullable();
            $table->string('surgery_site')->nullable();
            $table->string('operation_plan_actual')->nullable();
            $table->foreignId('anesthesia_type_id')->nullable()->constrained('anesthesia_types')->restrictOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->restrictOnDelete();
            $table->foreignId('surgeon_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('anesthesiologist_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('circulating_nurse_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('scrub_nurse_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('anesthesia_nurse_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('team_assigned_at')->nullable();
            $table->dateTime('ready_at')->nullable();
            $table->dateTime('entered_or_at')->nullable();
            $table->dateTime('anesthesia_start_at')->nullable();
            $table->dateTime('incision_at')->nullable();
            $table->dateTime('surgery_end_at')->nullable();
            $table->dateTime('left_or_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->json('stage_readiness_flags')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgery_cases');
    }
};
