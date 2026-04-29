<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->dropIndex(['email', 'otp', 'used_at']);
            $table->string('otp')->change();
            $table->index(['email', 'used_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->dropIndex(['email', 'used_at']);
            $table->string('otp', 6)->change();
            $table->index(['email', 'otp', 'used_at']);
        });
    }
};
