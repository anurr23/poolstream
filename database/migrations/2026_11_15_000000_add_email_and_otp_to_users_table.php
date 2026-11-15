<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('username');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('google_id')->nullable()->unique()->after('photo_path');
            $table->string('otp_code')->nullable()->after('google_id');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->unsignedTinyInteger('otp_attempts')->default(0)->after('otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropUnique(['google_id']);
            $table->dropColumn([
                'email',
                'email_verified_at',
                'google_id',
                'otp_code',
                'otp_expires_at',
                'otp_attempts',
            ]);
        });
    }
};
