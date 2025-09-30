<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer l'utilisateur administrateur par défaut
        User::firstOrCreate(
            ['email' => 'admin@agfarythmo.com'],
            [
                'name' => 'Administrateur AgfaRythmo',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Créer un utilisateur normal pour les tests
        User::firstOrCreate(
            ['email' => 'user@agfarythmo.com'],
            [
                'name' => 'Utilisateur Test',
                'password' => Hash::make('user123'),
                'role' => 'user',
            ]
        );
    }
}
