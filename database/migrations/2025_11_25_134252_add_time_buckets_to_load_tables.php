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
        // Note: This migration is designed to be run manually per user database
        // You need to connect to each user's database and run this migration
        
        $tables = ['light_loads', 'medium_loads', 'heavy_loads', 'universal_loads'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    // Daily buckets (4-hour intervals)
                    $table->integer('h4')->default(0);
                    $table->integer('h8')->default(0);
                    $table->integer('h12')->default(0);
                    $table->integer('h16')->default(0);
                    $table->integer('h20')->default(0);
                    $table->integer('h24')->default(0);
                    
                    // Weekly buckets (days of week)
                    $table->integer('mon')->default(0);
                    $table->integer('tue')->default(0);
                    $table->integer('wed')->default(0);
                    $table->integer('thu')->default(0);
                    $table->integer('fri')->default(0);
                    $table->integer('sat')->default(0);
                    $table->integer('sun')->default(0);
                    
                    // Monthly buckets (weeks)
                    $table->integer('week1')->default(0);
                    $table->integer('week2')->default(0);
                    $table->integer('week3')->default(0);
                    $table->integer('week4')->default(0);
                    
                    // Yearly buckets (months)
                    $table->integer('jan')->default(0);
                    $table->integer('feb')->default(0);
                    $table->integer('mar')->default(0);
                    $table->integer('apr')->default(0);
                    $table->integer('may')->default(0);
                    $table->integer('jun')->default(0);
                    $table->integer('jul')->default(0);
                    $table->integer('aug')->default(0);
                    $table->integer('sep')->default(0);
                    $table->integer('oct')->default(0);
                    $table->integer('nov')->default(0);
                    $table->integer('dec')->default(0);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['light_loads', 'medium_loads', 'heavy_loads', 'universal_loads'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn([
                        'h4', 'h8', 'h12', 'h16', 'h20', 'h24',
                        'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun',
                        'week1', 'week2', 'week3', 'week4',
                        'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'
                    ]);
                });
            }
        }
    }
};
