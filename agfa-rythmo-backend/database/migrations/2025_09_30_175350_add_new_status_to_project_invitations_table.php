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
        // Pour SQLite, nous devons utiliser une approche différente
        // Nous allons supprimer la contrainte d'unicité pour permettre multiple statuts
        // et gérer la logique dans l'application
        DB::statement('DROP INDEX IF EXISTS project_invitations_project_id_invited_user_id_status_unique');

        // Créer un nouvel index sans le status pour permettre les multiples invitations
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS project_invitations_project_user_pending_unique
                       ON project_invitations(project_id, invited_user_id)
                       WHERE status = "pending"');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurer l'ancienne contrainte d'unicité
        DB::statement('DROP INDEX IF EXISTS project_invitations_project_user_pending_unique');

        DB::statement('CREATE UNIQUE INDEX project_invitations_project_id_invited_user_id_status_unique
                       ON project_invitations(project_id, invited_user_id, status)');
    }
};
