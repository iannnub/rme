<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surgery_types', function (Blueprint $table) {
            $table->string('code', 40)->nullable()->unique()->after('id');
            $table->foreignId('default_medical_diagnosis_id')
                ->nullable()
                ->after('specialty')
                ->constrained('medical_diagnoses')
                ->nullOnDelete();
            $table->string('default_procedure_name', 255)->nullable()->after('default_medical_diagnosis_id');
        });

        Schema::table('instruments', function (Blueprint $table) {
            $table->string('surgery_profile', 30)->default('semua')->after('category')->index();
            $table->string('description', 255)->nullable()->after('surgery_profile');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->string('identity_number', 50)->nullable()->after('gender');
            $table->string('guardian_name', 150)->nullable()->after('phone');
            $table->string('guardian_relation', 80)->nullable()->after('guardian_name');
            $table->string('guardian_phone', 30)->nullable()->after('guardian_relation');
        });

        Schema::table('surgery_cases', function (Blueprint $table) {
            $table->string('igd_visit_number', 80)->nullable()->after('case_number');
            $table->dateTime('igd_admitted_at')->nullable()->after('admitted_at');
            $table->string('urgency_status', 30)->nullable()->after('origin_class');
            $table->string('dpjp_name', 150)->nullable()->after('anesthesia_type_id');
            $table->string('postop_diagnosis', 255)->nullable()->after('operation_plan_actual');
            $table->dateTime('anesthesia_end_at')->nullable()->after('left_or_at');
        });

        Schema::table('preop_assessments', function (Blueprint $table) {
            $table->json('clinical_details')->nullable()->after('evaluation_notes');
        });

        Schema::table('intraop_records', function (Blueprint $table) {
            $table->json('procedure_details')->nullable()->after('complication_handling');
            $table->json('implants')->nullable()->after('procedure_details');
        });

        Schema::table('postop_observations', function (Blueprint $table) {
            $table->json('clinical_details')->nullable()->after('notes');
        });

        Schema::table('postop_summaries', function (Blueprint $table) {
            $table->json('followup_plan')->nullable()->after('pre_transfer_condition_notes');
        });
    }

    public function down(): void
    {
        Schema::table('postop_summaries', function (Blueprint $table) {
            $table->dropColumn('followup_plan');
        });

        Schema::table('postop_observations', function (Blueprint $table) {
            $table->dropColumn('clinical_details');
        });

        Schema::table('intraop_records', function (Blueprint $table) {
            $table->dropColumn(['procedure_details', 'implants']);
        });

        Schema::table('preop_assessments', function (Blueprint $table) {
            $table->dropColumn('clinical_details');
        });

        Schema::table('surgery_cases', function (Blueprint $table) {
            $table->dropColumn([
                'igd_visit_number',
                'igd_admitted_at',
                'urgency_status',
                'dpjp_name',
                'postop_diagnosis',
                'anesthesia_end_at',
            ]);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'identity_number',
                'guardian_name',
                'guardian_relation',
                'guardian_phone',
            ]);
        });

        Schema::table('instruments', function (Blueprint $table) {
            $table->dropIndex(['surgery_profile']);
            $table->dropColumn(['surgery_profile', 'description']);
        });

        Schema::table('surgery_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_medical_diagnosis_id');
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'default_procedure_name']);
        });
    }
};
