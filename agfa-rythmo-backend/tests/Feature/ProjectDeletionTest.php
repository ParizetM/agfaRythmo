<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\Timecode;
use App\Models\SceneChange;
use App\Models\Character;
use Illuminate\Support\Facades\DB;

class ProjectDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_deletion_removes_all_related_data(): void
    {
        // Créer un utilisateur propriétaire
        $owner = User::factory()->create(['role' => 'user']);

        // Créer un utilisateur collaborateur
        $collaborator = User::factory()->create(['role' => 'user']);

        // Créer un utilisateur invité
        $invited = User::factory()->create(['role' => 'user']);

        // Créer un projet
        $project = Project::create([
            'name' => 'Test Project',
            'description' => 'Test Description',
            'user_id' => $owner->id,
        ]);

        // Ajouter des données liées
        $project->collaborators()->attach($collaborator->id, ['permission' => 'edit']);

        ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_by' => $owner->id,
            'invited_user_id' => $invited->id,
            'permission' => 'view',
            'status' => 'pending',
        ]);

        Timecode::create([
            'project_id' => $project->id,
            'line_number' => 1,
            'start' => 0.0,
            'end' => 5.0,
            'text' => 'Test timecode',
        ]);

        SceneChange::create([
            'project_id' => $project->id,
            'timecode' => 10.5,
        ]);

        Character::create([
            'project_id' => $project->id,
            'name' => 'Test Character',
            'color' => '#FF0000',
        ]);

        // Vérifier que les données existent
        $this->assertEquals(1, Project::count());
        $this->assertEquals(1, DB::table('project_collaborators')->count());
        $this->assertEquals(1, ProjectInvitation::count());
        $this->assertEquals(1, Timecode::count());
        $this->assertEquals(1, SceneChange::count());
        $this->assertEquals(1, Character::count());

        // Supprimer le projet via l'API
        $this->actingAs($owner)
             ->delete("/api/projects/{$project->id}")
             ->assertSuccessful();

        // Vérifier que toutes les données ont été supprimées
        $this->assertEquals(0, Project::count());
        $this->assertEquals(0, DB::table('project_collaborators')->count());
        $this->assertEquals(0, ProjectInvitation::count());
        $this->assertEquals(0, Timecode::count());
        $this->assertEquals(0, SceneChange::count());
        $this->assertEquals(0, Character::count());
    }

    public function test_only_owner_can_delete_project(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);

        $project = Project::create([
            'name' => 'Test Project',
            'user_id' => $owner->id,
        ]);

        // Tenter de supprimer avec un autre utilisateur
        $this->actingAs($otherUser)
             ->delete("/api/projects/{$project->id}")
             ->assertStatus(403);

        // Vérifier que le projet existe toujours
        $this->assertEquals(1, Project::count());
    }

    public function test_admin_can_delete_any_project(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        $project = Project::create([
            'name' => 'Test Project',
            'user_id' => $owner->id,
        ]);

        // L'admin peut supprimer n'importe quel projet
        $this->actingAs($admin)
             ->delete("/api/projects/{$project->id}")
             ->assertSuccessful();

        $this->assertEquals(0, Project::count());
    }
}
