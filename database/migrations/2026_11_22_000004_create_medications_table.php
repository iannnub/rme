<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('surgery_cases')->cascadeOnDelete();
            $table->string('phase', 20)->default('intra');
            $table->string('name', 150);
            $table->string('dose', 100);
            $table->string('route', 50)->nullable();
            $table->timestamp('given_at');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_id', 'phase', 'given_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
