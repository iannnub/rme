<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('surgery_cases')->cascadeOnDelete();
            $table->string('destination', 30);
            $table->string('destination_detail', 150)->nullable();
            $table->boolean('documents_complete')->default(true);
            $table->foreignId('handed_by_id')->constrained('users')->restrictOnDelete();
            $table->string('received_by_name', 150);
            $table->foreignId('received_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('handed_over_at');
            $table->text('ongoing_issues')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->json('summary_snapshot')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handovers');
    }
};
