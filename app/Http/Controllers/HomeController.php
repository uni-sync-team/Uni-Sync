<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Filiere;
use App\Models\Formation;
use App\Models\Publication;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            // eager loading avoids N+1 queries in the nested loops
            'departements' => Departement::with('filieres.formations')->get(),
            'publications' => Publication::active()->take(4)->get(),
            'stats' => [
                'etudiants'   => Etudiant::count(),
                'enseignants' => Enseignant::count(),
                'filieres'    => Filiere::count(),
                'formations'  => Formation::count(),
            ],
            'annee' => '2026/2027',
        ]);
    }
}
