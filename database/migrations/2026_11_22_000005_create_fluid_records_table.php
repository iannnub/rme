<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fluid_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('surgery_cases')->cascadeOnDelete();
            $table->string('phase', 20)->default('intra');
            $table->timestamp('recorded_at')->nullable();
            $table->string('direction', 20); // intake|output
            $table->string('kind', 30); // kristaloid|koloid|transfusi|perdarahan|urine|suction_drain
            $table->unsignedInteger('volume_ml');
            $table->string('color', 30)->nullable();
            $table->string('odor', 30)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_id', 'phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fluid_records');
    }
};
