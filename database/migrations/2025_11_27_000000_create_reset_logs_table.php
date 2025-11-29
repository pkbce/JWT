<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration is intended to be run on the specific user database
        
        if (!Schema::hasTable('reset_logs')) {
            Schema::create('reset_logs', function (Blueprint $table) {
                $table->id();
                $table->string('reset_type')->unique(); // daily, weekly, monthly, yearly
                $table->timestamp('last_reset_at')->useCurrent();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
            });

            // Seed initial data
            DB::table('reset_logs')->insert([
                ['reset_type' => 'daily', 'last_reset_at' => now()],
                ['reset_type' => 'weekly', 'last_reset_at' => now()],
                ['reset_type' => 'monthly', 'last_reset_at' => now()],
                ['reset_type' => 'yearly', 'last_reset_at' => now()],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reset_logs');
    }
};
