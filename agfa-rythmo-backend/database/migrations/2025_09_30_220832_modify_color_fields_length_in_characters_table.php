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
        Schema::table('characters', function (Blueprint $table) {
            // Modifier la taille du champ color existant pour supporter RGBA
            $table->string('color', 50)->change();
            // Modifier la taille du champ text_color pour supporter RGBA
            $table->string('text_color', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            // Revenir aux tailles précédentes
            $table->string('color', 7)->change();
            $table->string('text_color', 7)->nullable()->change();
        });
    }
};
