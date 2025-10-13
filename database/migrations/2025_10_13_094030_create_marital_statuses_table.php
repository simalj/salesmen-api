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
        Schema::create('marital_statuses', function (Blueprint $table) {
            $table->string('code', 20)->primary(); // 'single', 'married', 'divorced', 'widowed'
            $table->string('name_m', 50); // 'slobodný', 'ženatý', 'rozvedený', 'vdovec'
            $table->string('name_f', 50); // 'slobodná', 'vydatá', 'rozvedená', 'vdova'
            $table->string('name_general', 50); // 'slobodný / slobodná', atď.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marital_statuses');
    }
};
