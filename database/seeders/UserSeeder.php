<?php

namespace Database\Seeders;

use App\Models\Administrateur;
use App\Models\Departement;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Same bcrypt hash as your old seed, so the test passwords are unchanged.
        // User's 'hashed' cast keeps a value that is already a bcrypt hash as it is.
        $hash = '$2y$10$ve91ysUlxWUobz4xl8kcAuI4.5W9mG9GeKPCH.dzhTfKk9BQ95Ase';

        // login (= role name) => email
        $comptes = [
            'etudiant' => 'etud@test.com',
            'prof'     => 'prof@test.com',
            'admin'    => 'admin@test.com',
        ];

        $users = [];
        foreach ($comptes as $login => $email) {
            $user = User::create([
                'login'    => $login,
                'email'    => $email,
                'password' => $hash,
            ]);
            $user->roles()->attach(Role::where('nom', $login)->firstOrFail());
            $users[$login] = $user;
        }

        // Profil administration (required by publications.administrateur_id)
        Administrateur::create([
            'user_id' => $users['admin']->id,
            'nom'     => 'Admin',
            'prenom'  => 'Principal',
            'service' => 'Scolarité',
        ]);

        Enseignant::create([
            'departement_id' => Departement::where('nom', 'Informatique')->firstOrFail()->id,
            'user_id'        => $users['prof']->id,
            'nom'            => 'Test',
            'prenom'         => 'Prof',
            'grade'          => 'Professeur Assistant',
        ]);

        Etudiant::create([
            'user_id'        => $users['etudiant']->id,
            'cne'            => 'CNE0000001',
            'nom'            => 'Test',
            'prenom'         => 'Etudiant',
            'telephone'      => '0623974921',
            'date_naissance' => today(),
        ]);
    }
}
