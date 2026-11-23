<?php

namespace Database\Seeders;

use App\Models\Administrateur;
use App\Models\Publication;
use Illuminate\Database\Seeder;

class PublicationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Administrateur::firstOrFail();

        // titre, contenu, début (jours depuis aujourd'hui), fin (jours ou null), actif
        $annonces = [
            // 4 visible announcements for the home page
            ['Notes du semestre 3 publiées', 'Connectez-vous pour consulter vos notes par module et par évaluation.', -1, null, true],
            ['Réclamations ouvertes', 'Vous pouvez contester une note en indiquant le motif. Suivez le statut dans votre espace.', -3, null, true],
            ['Calendrier des examens', 'Les séances et les salles sont publiées par l’administration du département.', -6, null, true],
            ['Inscriptions aux modules', 'Vérifiez votre inscription à la formation et à vos modules pour l’année universitaire.', -9, null, true],

            // not visible on the home page: scheduled, expired, disabled
            ['Journée portes ouvertes', 'Le département ouvre ses portes aux futurs étudiants.', 10, null, true],
            ['Dépôt des dossiers de bourse', 'La période de dépôt est terminée.', -30, -1, true],
            ['Brouillon : règlement intérieur', 'Texte en cours de relecture.', 0, null, false],
        ];

        foreach ($annonces as [$titre, $contenu, $debut, $fin, $actif]) {
            Publication::create([
                'administrateur_id' => $admin->id,
                'titre'             => $titre,
                'contenu'           => $contenu,
                'date_publication'  => today()->addDays($debut),
                'date_expiration'   => $fin === null ? null : today()->addDays($fin),
                'actif'             => $actif,
            ]);
        }
    }
}
