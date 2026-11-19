<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssc_time_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('surgery_cases')->cascadeOnDelete();
            $table->string('status', 30)->default('belum_dimulai');

            // 1. Perkenalan tim (5 anggota)
            $table->boolean('intro_circulating')->default(false);
            $table->boolean('intro_scrub')->default(false);
            $table->boolean('intro_anesthesia_nurse')->default(false);
            $table->boolean('intro_surgeon')->default(false);
            $table->boolean('intro_anesthesiologist')->default(false);

            // 2. Konfirmasi identitas, tindakan, lokasi
            $table->boolean('identity_procedure_site_confirmed')->default(false);

            // 3. Antibiotik profilaksis
            $table->string('antibiotic_prophylaxis', 20)->nullable(); // ya|na
            $table->string('antibiotic_name', 150)->nullable();
            $table->timestamp('antibiotic_given_at')->nullable();
            $table->string('antibiotic_confirmed_by', 150)->nullable();

            // Blok Dokter Operator
            $table->string('surgeon_critical_steps', 20)->nullable(); // ya|tidak
            $table->text('surgeon_critical_steps_text')->nullable();
            $table->unsignedInteger('surgeon_estimated_duration_min')->nullable();
            $table->unsignedInteger('surgeon_estimated_blood_loss_ml')->nullable();

            // Blok Dokter Anestesi
            $table->string('anesthesia_special_condition', 20)->nullable(); // ya|tidak
            $table->string('anesthesia_risk', 20)->nullable(); // ya|tidak
            $table->text('anesthesia_note')->nullable();

            // Blok Tim Perawat
            $table->boolean('nurse_sterility_confirmed')->default(false);
            $table->boolean('nurse_indicator_checked')->default(false);
            $table->string('equipment_problem', 20)->nullable(); // ya|tidak
            $table->string('other_concern', 20)->nullable(); // ya|tidak
            $table->text('nurse_note')->nullable();

            // Blok Pencitraan Medis
            $table->string('imaging_needed', 20)->nullable(); // ya|tidak
            $table->string('imaging_displayed', 20)->nullable(); // ya|tidak|null
            $table->string('imaging_matches', 20)->nullable(); // ya|tidak|null

            $table->timestamp('signed_at')->nullable();
            $table->foreignId('filled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssc_time_outs');
    }
};
