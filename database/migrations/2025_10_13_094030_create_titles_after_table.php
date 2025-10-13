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
        Schema::create('titles_after', function (Blueprint $table) {
            $table->string('code', 15)->primary(); // 'CSc.', 'DrSc.', 'PhD.', atď.  
            $table->string('name', 15); // rovnaké ako code
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('titles_after');
    }
};
