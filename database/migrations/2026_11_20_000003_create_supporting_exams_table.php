<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supporting_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('surgery_cases')->restrictOnDelete();
            $table->string('exam_type');
            $table->text('result');
            $table->date('exam_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_id', 'exam_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supporting_exams');
    }
};
