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
        Schema::table('salesmen', function (Blueprint $table) {
            // Performance indexes for common queries
            $table->index('email'); // For unique email searches
            $table->index('prosight_id'); // For unique prosight_id searches  
            $table->index('gender_code'); // For filtering by gender
            $table->index('marital_status_code'); // For filtering by marital status
            $table->index(['first_name', 'last_name']); // For name-based searches
            $table->index('created_at'); // For date-based sorting
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salesmen', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['prosight_id']);
            $table->dropIndex(['gender_code']);
            $table->dropIndex(['marital_status_code']);
            $table->dropIndex(['first_name', 'last_name']);
            $table->dropIndex(['created_at']);
        });
    }
};
