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
        Schema::create('titles_before', function (Blueprint $table) {
            $table->string('code', 15)->primary(); // 'Bc.', 'Mgr.', 'Ing.', atď.
            $table->string('name', 15); // rovnaké ako code (v zadaní sú identické)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('titles_before');
    }
};
