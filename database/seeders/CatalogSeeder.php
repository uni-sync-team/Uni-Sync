<?php

namespace Database\Seeders;

use App\Models\Departement;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // département => [description, filières => [description, formations => [[intitulé, niveau, durée]]]]
        $catalogue = [
            'Informatique' => [
                'Génie logiciel, intelligence artificielle, réseaux et systèmes.',
                [
                    'Génie Informatique' => [
                        'Conception et développement de logiciels et de systèmes d’information.',
                        [
                            ['Licence en Génie Informatique', 'Licence', 3],
                            ['Master en Génie Logiciel', 'Master', 2],
                        ],
                    ],
                    'Intelligence Artificielle' => [
                        'Apprentissage automatique, vision par ordinateur et traitement du langage.',
                        [
                            ['Licence en Intelligence Artificielle', 'Licence', 3],
                            ['Master en Intelligence Artificielle', 'Master', 2],
                        ],
                    ],
                ],
            ],
            'Mathématiques' => [
                'Mathématiques appliquées, statistique et analyse de données.',
                [
                    'Mathématiques Appliquées' => [
                        'Modélisation, optimisation et calcul scientifique.',
                        [
                            ['Licence en Mathématiques Appliquées', 'Licence', 3],
                        ],
                    ],
                    'Statistique et Data Science' => [
                        'Statistique, probabilités et science des données.',
                        [
                            ['Master en Data Science', 'Master', 2],
                        ],
                    ],
                ],
            ],
            'Physique' => [
                'Physique fondamentale, électronique et automatique.',
                [
                    'Électronique et Automatique' => [
                        'Systèmes électroniques, capteurs et commande.',
                        [
                            ['Licence en Électronique', 'Licence', 3],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($catalogue as $nomDepartement => [$descDepartement, $filieres]) {
            $departement = Departement::create([
                'nom'         => $nomDepartement,
                'description' => $descDepartement,
            ]);

            foreach ($filieres as $nomFiliere => [$descFiliere, $formations]) {
                $filiere = $departement->filieres()->create([
                    'nom'         => $nomFiliere,
                    'description' => $descFiliere,
                ]);

                foreach ($formations as [$intitule, $niveau, $duree]) {
                    $filiere->formations()->create([
                        'intitule' => $intitule,
                        'niveau'   => $niveau,
                        'duree'    => $duree,
                    ]);
                }
            }
        }
    }
}
