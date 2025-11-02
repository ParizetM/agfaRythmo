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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('instrumental_audio_path')->nullable()->after('video_path');
            $table->string('instrumental_status')->default('not_generated')->after('instrumental_audio_path'); // not_generated, processing, completed, failed
            $table->integer('instrumental_progress')->default(0)->after('instrumental_status'); // 0-100%
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['instrumental_audio_path', 'instrumental_status', 'instrumental_progress']);
        });
    }
};
