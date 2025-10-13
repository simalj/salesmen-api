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
        Schema::create('salesmen', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID primary key
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->json('titles_before')->nullable(); // JSON array of titles
            $table->json('titles_after')->nullable(); // JSON array of titles
            $table->string('prosight_id', 5)->unique(); // presne 5 znakov, unique
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('gender_code', 1); // foreign key na genders.code
            $table->string('marital_status_code', 20)->nullable(); // foreign key na marital_statuses.code
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('gender_code')->references('code')->on('genders');
            $table->foreign('marital_status_code')->references('code')->on('marital_statuses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salesmen');
    }
};
