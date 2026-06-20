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
        Schema::table('tracked_games', function (Blueprint $table) {
            $table->dropColumn(['notify_email', 'notify_telegram']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracked_games', function (Blueprint $table) {
            $table->boolean('notify_email')->default(false);
            $table->boolean('notify_telegram')->default(true);
        });
    }
};
