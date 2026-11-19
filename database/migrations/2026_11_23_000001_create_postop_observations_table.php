<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postop_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('surgery_cases')->cascadeOnDelete();
            $table->timestamp('recorded_at');

            // 1. Kondisi Umum & GCS
            $table->unsignedTinyInteger('gcs_eye');
            $table->unsignedTinyInteger('gcs_verbal');
            $table->unsignedTinyInteger('gcs_motor');
            $table->string('general_condition', 20);
            $table->string('response', 20);

            // 2. Pernapasan
            $table->string('airway_patency', 30);

            // 3. Kondisi Luka Operasi
            $table->string('wound_location', 150)->nullable();
            $table->string('dressing', 20)->nullable();
            $table->boolean('bleeding_seepage')->default(false);
            $table->boolean('swelling_hematoma')->default(false);
            $table->string('suture_condition', 20)->nullable();

            // 4. Tingkat Nyeri
            $table->unsignedTinyInteger('pain_nrs')->default(0);
            $table->string('pain_location', 150)->nullable();
            $table->string('pain_character', 20)->nullable();
            $table->string('pain_duration', 100)->nullable();
            $table->string('analgesic_response', 20)->nullable();

            // 5. Efek Anestesi & Komplikasi
            $table->string('anesthesia_recovery', 30)->nullable();
            $table->boolean('nausea_vomiting')->default(false);
            $table->boolean('shivering_hypothermia')->default(false);
            $table->json('complications')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postop_observations');
    }
};
