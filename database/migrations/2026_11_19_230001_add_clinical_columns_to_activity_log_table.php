<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->unsignedBigInteger('case_id')->nullable()->after('batch_uuid')->index();
            $table->text('reason')->nullable()->after('case_id');
            $table->string('ip_address', 45)->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropIndex(['case_id']);
            $table->dropColumn(['case_id', 'reason', 'ip_address']);
        });
    }
};
