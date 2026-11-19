<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssc_sign_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('surgery_cases')->cascadeOnDelete();
            $table->string('status', 30)->default('belum_dimulai');

            // B. Konfirmasi Identitas dan Tindakan Operasi
            $table->string('identity_confirmed', 30)->nullable();
            $table->string('procedure_confirmed', 30)->nullable();
            $table->string('site_confirmed', 30)->nullable();
            $table->string('consent_surgery', 30)->nullable();
            $table->string('consent_anesthesia', 30)->nullable();
            $table->string('site_marking', 30)->nullable();

            // C. Kesiapan Alat dan Obat Anestesi (7 kunci JSON)
            $table->json('readiness')->nullable();

            // D. Pemeriksaan Pulse Oximeter
            $table->boolean('pulse_ox_attached')->nullable();
            $table->boolean('pulse_ox_functional')->nullable();
            $table->unsignedTinyInteger('spo2')->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->text('pulse_ox_note')->nullable();

            // E. Pemeriksaan Riwayat Alergi
            $table->string('allergy_status', 30)->nullable();
            $table->string('allergy_type', 100)->nullable();
            $table->string('allergy_agent', 150)->nullable();
            $table->text('allergy_reaction')->nullable();
            $table->text('allergy_followup')->nullable();

            // F. Pemeriksaan Risiko Jalan Napas dan Aspirasi
            $table->string('difficult_airway_risk', 30)->nullable();
            $table->string('aspiration_risk', 30)->nullable();
            $table->text('anesthesiologist_assessment')->nullable();
            $table->text('anticipation_plan')->nullable();
            $table->string('airway_equipment_ready', 30)->nullable();

            // G. Pemeriksaan Risiko Perdarahan
            $table->string('bleeding_risk', 30)->nullable();
            $table->unsignedInteger('estimated_blood_loss_ml')->nullable();
            $table->string('iv_access', 30)->nullable();
            $table->string('fluids_ready', 30)->nullable();
            $table->string('blood_ready', 30)->nullable();
            $table->text('bleeding_note')->nullable();

            // Dokumentasi dan Tindak Lanjut
            $table->text('notes_followup')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('filled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssc_sign_ins');
    }
};
