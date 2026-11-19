<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('surgery_cases')->cascadeOnDelete();
            $table->string('phase', 20)->default('intra');
            $table->timestamp('performed_at');
            $table->string('performed_by_role', 20); // sirkuler|instrumen
            $table->text('action');
            $table->text('evaluation')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_id', 'phase', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_actions');
    }
};
