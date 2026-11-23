<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // The order matters because of the foreign keys.
        $this->call([
            RoleSeeder::class,        // roles
            CatalogSeeder::class,     // départements, filières, formations
            UserSeeder::class,        // users + admin / teacher / student profiles
            PublicationSeeder::class, // needs the admin profile
            AcademicSeeder::class,    // needs the formations and the test accounts
        ]);
    }
}
