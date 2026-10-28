<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel's password broker expects an "email" column in the token table.
     * This application authenticates with a username, so the column stores the
     * username while keeping the framework's default column name.
     */
    public function up(): void
    {
        if (Schema::hasColumn('password_reset_tokens', 'username')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->renameColumn('username', 'email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('password_reset_tokens', 'email')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->renameColumn('email', 'username');
            });
        }
    }
};
