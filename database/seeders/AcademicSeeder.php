<?php

namespace Database\Seeders;

use App\Models\Administrateur;
use App\Models\AnneeUniversitaire;
use App\Models\DemandeAdministrative;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Notification;
use App\Models\ReclamationNote;
use App\Models\ResultatSemestre;
use App\Models\Salle;
use App\Models\Seance;
use App\Models\User;
use Illuminate\Database\Seeder;

class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        $annee = AnneeUniversitaire::create([
            'libelle'    => '2026-2027',
            'date_debut' => '2026-09-01',
            'date_fin'   => '2027-07-31',
        ]);

        /* ----- semestres and modules (one semester per licence) ----- */
        $plan = [
            'Licence en Génie Informatique' => [
                ['INF101', 'Algorithmique et programmation', 60, 2],
                ['INF102', 'Bases de données', 50, 2],
            ],
            'Licence en Intelligence Artificielle' => [
                ['IA101', 'Introduction à l’IA', 50, 2],
                ['IA102', 'Python pour la data', 40, 1.5],
            ],
            'Licence en Mathématiques Appliquées' => [
                ['MAT101', 'Analyse', 60, 2],
                ['MAT102', 'Algèbre linéaire', 50, 2],
            ],
        ];

        $semestres = [];   // formation title => Semestre
        $modules   = [];   // module code => Module
        foreach ($plan as $intitule => $liste) {
            $formation = Formation::where('intitule', $intitule)->firstOrFail();
            $semestre  = $formation->semestres()->create(['libelle' => 'Semestre 1', 'numero' => 1]);
            $semestres[$intitule] = $semestre;

            foreach ($liste as [$code, $moduleIntitule, $volume, $coef]) {
                $modules[$code] = $semestre->modules()->create([
                    'code'           => $code,
                    'intitule'       => $moduleIntitule,
                    'volume_horaire' => $volume,
                    'coefficient'    => $coef,
                ]);
            }
        }

        /* ----- salles ----- */
        $salles = [
            'Salle 101' => Salle::create(['nom' => 'Salle 101', 'capacite' => 40, 'type_salle' => 'Salle de cours']),
            'Amphi A'   => Salle::create(['nom' => 'Amphi A', 'capacite' => 200, 'type_salle' => 'Amphithéâtre']),
        ];

        /* ----- séances: a timetable for the current week (Monday = day 0) ----- */
        $prof   = Enseignant::where('nom', 'Test')->where('prenom', 'Prof')->firstOrFail();
        $lundi  = today()->startOfWeek();
        $seances = [
            // module, salle, jour, début, fin, type
            ['INF101', 'Amphi A',   0, '08:30', '10:30', 'Cours'],
            ['INF101', 'Salle 101', 0, '10:45', '12:45', 'TD'],
            ['INF102', 'Amphi A',   1, '08:30', '10:30', 'Cours'],
            ['INF102', 'Salle 101', 1, '14:00', '16:00', 'TP'],
            ['IA101',  'Amphi A',   2, '09:00', '11:00', 'Cours'],
            ['IA101',  'Salle 101', 3, '10:00', '12:00', 'TD'],
            ['MAT101', 'Amphi A',   4, '08:30', '10:30', 'Cours'],
        ];
        foreach ($seances as [$code, $salle, $jour, $debut, $fin, $type]) {
            $jourDate = $lundi->copy()->addDays($jour);

            Seance::create([
                'module_id'     => $modules[$code]->id,
                'enseignant_id' => $prof->id,
                'salle_id'      => $salles[$salle]->id,
                'date_debut'    => $jourDate->copy()->setTimeFromTimeString($debut),
                'date_fin'      => $jourDate->copy()->setTimeFromTimeString($fin),
                'type_seance'   => $type,
            ]);
        }

        /* ----- évaluations ----- */
        $liste = [
            // module, type, date, barème, coefficient
            ['IA101',  'Contrôle continu', '2026-11-16', 20, 1],
            ['IA101',  'Examen final',     '2027-01-18', 20, 2],
            ['IA102',  'TP',               '2026-12-02', 20, 1],
            ['IA102',  'Examen final',     '2027-01-20', 20, 2],
            ['MAT101', 'Contrôle continu', '2026-11-20', 20, 1],
            ['MAT101', 'Examen final',     '2027-01-22', 20, 2],
            ['MAT102', 'Contrôle continu', '2026-11-25', 10, 1],
            ['MAT102', 'Examen final',     '2027-01-25', 20, 2],
            ['INF101', 'Rattrapage',       '2027-02-15', 20, 2],
            ['INF102', 'TP',               '2026-12-05', 20, 1],
            // already taken, so the test student has grades
            ['INF101', 'Contrôle continu', today()->subDays(30), 20, 1],
            ['INF101', 'Examen final',     today()->subDays(10), 20, 2],
            ['INF102', 'Examen final',     today()->subDays(8),  20, 2],
        ];

        $evaluations = [];   // "CODE|type" => Evaluation
        foreach ($liste as [$code, $type, $date, $bareme, $coef]) {
            $evaluations["$code|$type"] = $modules[$code]->evaluations()->create([
                'type'            => $type,
                'date_evaluation' => $date,
                'bareme'          => $bareme,
                'coefficient'     => $coef,
            ]);
        }

        /* ----- the test student: enrollment, grades, results ----- */
        $etudiant = Etudiant::where('cne', 'CNE0000001')->firstOrFail();
        $licenceGI = Formation::where('intitule', 'Licence en Génie Informatique')->firstOrFail();

        $inscription = Inscription::create([
            'etudiant_id'            => $etudiant->id,
            'formation_id'           => $licenceGI->id,
            'annee_universitaire_id' => $annee->id,
            'statut'                 => Inscription::STATUT_VALIDEE,
        ]);
        // enrolled in the two modules that have grades (module status left empty)
        $inscription->enroll($modules['INF101']->id);
        $inscription->enroll($modules['INF102']->id);

        Note::saveNote($etudiant->id, $evaluations['INF101|Contrôle continu']->id, 16.00);
        Note::saveNote($etudiant->id, $evaluations['INF101|Examen final']->id, 15.00);
        Note::saveNote($etudiant->id, $evaluations['INF102|Examen final']->id, 13.50);

        // computes the semester average from the grades: should give 14.42 (VALIDE)
        ResultatSemestre::calculer($semestres['Licence en Génie Informatique']->id);

        /* ----- demandes administratives ----- */
        $admin = Administrateur::firstOrFail();
        $demandes = [
            // type, il y a N jours, statut, traitée par
            ['Attestation de scolarité', 1,  DemandeAdministrative::STATUT_EN_ATTENTE, null],
            ['Relevé de notes',          3,  DemandeAdministrative::STATUT_EN_ATTENTE, null],
            ['Certificat de réussite',   12, DemandeAdministrative::STATUT_TRAITEE,    $admin->id],
        ];
        foreach ($demandes as [$type, $jours, $statut, $adminId]) {
            DemandeAdministrative::create([
                'etudiant_id'       => $etudiant->id,
                'administrateur_id' => $adminId,
                'type_demande'      => $type,
                'date_demande'      => now()->subDays($jours),
                'statut'            => $statut,
            ]);
        }

        /* ----- réclamations ----- */
        ReclamationNote::create([
            'etudiant_id'   => $etudiant->id,
            'evaluation_id' => $evaluations['INF101|Contrôle continu']->id,
            'motif'         => 'Je pense qu’une question du contrôle a été mal corrigée, merci de revoir ma copie.',
            'statut'        => ReclamationNote::STATUT_EN_ATTENTE,
        ]);
        ReclamationNote::create([
            'etudiant_id'   => $etudiant->id,
            'evaluation_id' => $evaluations['INF102|Examen final']->id,
            'motif'         => 'Erreur dans le calcul du total des points.',
            'statut'        => ReclamationNote::STATUT_REJETEE,
        ]);

        /* ----- notifications ----- */
        foreach (User::all() as $user) {
            Notification::create([
                'user_id' => $user->id,
                'message' => 'Bienvenue sur UniSync. Votre compte est prêt.',
                'lu'      => $user->login === 'etudiant',
            ]);
        }
        Notification::sendToUser($etudiant->user_id, 'Les notes du contrôle continu de INF101 sont disponibles.');
        Notification::sendToUser($etudiant->user_id, 'N’oubliez pas de vérifier votre inscription aux modules.');
    }
}
