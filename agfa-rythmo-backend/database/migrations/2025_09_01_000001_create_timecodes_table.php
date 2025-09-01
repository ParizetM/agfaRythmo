<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('timecodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedTinyInteger('line_number')->default(1); // 1 à 6
            $table->float('start');
            $table->float('end');
            $table->text('text');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->index(['project_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timecodes');
    }
};
