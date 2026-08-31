<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->string('mail_from_name')->nullable();
            $table->string('mail_from_address')->nullable();
            $table->string('mail_reply_to')->nullable();
            $table->string('password_reset_subject')->nullable();
            $table->string('absence_request_subject')->nullable();
            $table->string('absence_approved_subject')->nullable();
            $table->string('absence_rejected_subject')->nullable();
            $table->string('work_time_incident_subject')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'mail_from_name', 'mail_from_address', 'mail_reply_to',
                'password_reset_subject', 'absence_request_subject',
                'absence_approved_subject', 'absence_rejected_subject',
                'work_time_incident_subject',
            ]);
        });
    }
};
