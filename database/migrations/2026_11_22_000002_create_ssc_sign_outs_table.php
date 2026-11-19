<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssc_sign_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('surgery_cases')->cascadeOnDelete();
            $table->string('status', 30)->default('draf');

            // 5 Pertanyaan Utama WHO (tidak tercentang otomatis -> nullable)
            $table->boolean('q1_procedure_confirmed')->nullable();
            $table->boolean('q2_counts_correct')->nullable();
            $table->string('q3_specimen_labeled', 30)->nullable(); // ya|tidak|tidak_berlaku
            $table->boolean('q4_equipment_problem')->nullable();
            $table->boolean('q5_recovery_discussed')->nullable();

            // 8 Pertanyaan Tambahan Terpilih (kondisional)
            $table->string('extra_6_procedure_matches', 30)->nullable(); // sesuai|belum_sesuai
            $table->string('extra_7_changes_recorded', 30)->nullable(); // sudah|belum|tidak_ada_perubahan
            $table->string('extra_8_discrepancy_followup', 30)->nullable(); // sudah|belum|tidak_ada_selisih
            $table->string('extra_9_specimen_matches', 30)->nullable(); // sesuai|belum_sesuai|tidak_ada_spesimen
            $table->string('extra_10_equipment_issue_recorded', 30)->nullable(); // sudah|belum|tidak_ada_masalah
            $table->string('extra_11_complication_communicated', 30)->nullable(); // sudah|belum|tidak_ada_komplikasi
            $table->string('extra_12_postop_monitoring_discussed', 30)->nullable(); // sudah|belum
            $table->string('extra_13_devices_instructions', 30)->nullable(); // sudah|belum|tidak_berlaku

            $table->text('recovery_notes')->nullable();
            $table->text('issues_and_actions')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('filled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssc_sign_outs');
    }
};
