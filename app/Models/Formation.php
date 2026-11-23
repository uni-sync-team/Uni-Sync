<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formation extends Model
{
    protected $fillable = ['filiere_id', 'intitule', 'niveau', 'duree'];

    protected $casts = [
        'duree' => 'integer',
    ];

    /** Replaces the JOIN on filiere: $formation->filiere->nom */
    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    /**
     * Replaces getSemestres($id): $formation->semestres
     * Uses the formation_id column of the semestres table.
     */
    public function semestres(): HasMany
    {
        return $this->hasMany(Semestre::class)->orderBy('numero');
    }

    /**
     * Replaces getEtudiants($idFormation, $idAnnee).
     * Pivot table "inscriptions" (etudiant_id, formation_id, annee_universitaire_id,
     * date_inscription, statut).
     *
     * $formation->etudiantsDeLAnnee($anneeId)
     * Each student has ->pivot->statut (your old statutInscription).
     */
    public function etudiants(): BelongsToMany
    {
        return $this->belongsToMany(Etudiant::class, 'inscriptions')
            ->withPivot('statut', 'annee_universitaire_id', 'date_inscription');
    }

    public function etudiantsDeLAnnee(int $anneeId)
    {
        return $this->etudiants()
            ->wherePivot('annee_universitaire_id', $anneeId)
            ->orderBy('etudiants.nom')
            ->orderBy('etudiants.prenom')
            ->get();
    }

    /**
     * Replaces findAllWithFiliere():
     * Formation::withFiliereNom()->get()  (each row has ->filiere as a string)
     */
    public function scopeWithFiliereNom(Builder $query): Builder
    {
        return $query
            ->join('filieres', 'filieres.id', '=', 'formations.filiere_id')
            ->select('formations.*', 'filieres.nom as filiere')
            ->orderBy('filieres.nom')
            ->orderBy('formations.intitule');
    }
}
