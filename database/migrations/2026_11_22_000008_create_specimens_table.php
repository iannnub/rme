<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specimens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('surgery_cases')->cascadeOnDelete();
            $table->boolean('has_specimen')->default(false);
            $table->string('specimen_type', 150)->nullable();
            $table->string('origin_organ', 150)->nullable();
            $table->string('purpose', 40)->nullable();
            $table->string('preservative', 40)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('case_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specimens');
    }
};
