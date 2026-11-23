<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin'    => 'Administration',
            'prof'     => 'Enseignant',
            'etudiant' => 'Étudiant',
        ];

        foreach ($roles as $nom => $description) {
            Role::updateOrCreate(['nom' => $nom], ['description' => $description]);
        }
    }
}
