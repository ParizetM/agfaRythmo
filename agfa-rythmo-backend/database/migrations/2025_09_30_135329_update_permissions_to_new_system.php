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
        // Mettre à jour les permissions dans project_collaborators
        Schema::table('project_collaborators', function (Blueprint $table) {
            // Changer l'enum pour inclure les nouvelles valeurs
            $table->dropColumn('permission');
        });

        Schema::table('project_collaborators', function (Blueprint $table) {
            $table->enum('permission', ['view', 'edit', 'admin'])->default('view')->after('user_id');
        });

        // Mettre à jour les permissions dans project_invitations
        Schema::table('project_invitations', function (Blueprint $table) {
            $table->dropColumn('permission');
        });

        Schema::table('project_invitations', function (Blueprint $table) {
            $table->enum('permission', ['view', 'edit', 'admin'])->default('view')->after('invited_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retour aux anciennes permissions
        Schema::table('project_collaborators', function (Blueprint $table) {
            $table->dropColumn('permission');
        });

        Schema::table('project_collaborators', function (Blueprint $table) {
            $table->enum('permission', ['read', 'write', 'admin'])->default('read')->after('user_id');
        });

        Schema::table('project_invitations', function (Blueprint $table) {
            $table->dropColumn('permission');
        });

        Schema::table('project_invitations', function (Blueprint $table) {
            $table->enum('permission', ['read', 'write', 'admin'])->default('read')->after('invited_user_id');
        });
    }
};
