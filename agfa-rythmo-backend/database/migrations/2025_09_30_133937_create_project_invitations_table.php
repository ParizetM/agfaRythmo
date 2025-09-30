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
        Schema::create('project_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade'); // Qui invite
            $table->foreignId('invited_user_id')->constrained('users')->onDelete('cascade'); // Qui est invité
            $table->enum('permission', ['read', 'write', 'admin'])->default('read');
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->text('message')->nullable(); // Message d'invitation optionnel
            $table->timestamp('expires_at')->nullable(); // Date d'expiration
            $table->timestamps();

            // Empêcher les doublons d'invitations en attente
            $table->unique(['project_id', 'invited_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_invitations');
    }
};
