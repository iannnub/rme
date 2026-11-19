<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_signoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('surgery_cases')->restrictOnDelete();
            $table->string('section');
            $table->string('kind');
            $table->string('role_label');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('nip_snapshot')->nullable();
            $table->string('sip_snapshot')->nullable();
            $table->dateTime('signed_at');
            $table->dateTime('revoked_at')->nullable();
            $table->string('revoke_reason')->nullable();
            $table->timestamps();

            $table->index(['case_id', 'section', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_signoffs');
    }
};
