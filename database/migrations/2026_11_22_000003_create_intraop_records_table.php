<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intraop_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('surgery_cases')->cascadeOnDelete();

            // Bagian 3: Persiapan posisi & area operasi
            $table->string('position', 30)->nullable();
            $table->json('pressure_protection')->nullable();
            $table->json('skin_antiseptic')->nullable();
            $table->string('grounding_plate_site', 150)->nullable();
            $table->string('skin_condition_pre', 20)->nullable();
            $table->string('skin_condition_post', 20)->nullable();
            $table->boolean('draping_done')->default(false);
            $table->json('warming')->nullable();
            $table->decimal('room_temperature_c', 4, 1)->nullable();

            // Bagian 6: Verifikasi akhir hitungan & tindak lanjut
            $table->string('count_verification', 30)->nullable();
            $table->json('count_discrepancy_actions')->nullable();

            // Bagian 7: Kejadian khusus / komplikasi
            $table->boolean('has_complication')->default(false);
            $table->text('complication_description')->nullable();
            $table->text('complication_handling')->nullable();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intraop_records');
    }
};
