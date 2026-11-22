<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->cascadeOnDelete();
            $table->foreignId('formation_id')->constrained('formations')->cascadeOnDelete();
            $table->foreignId('annee_universitaire_id')->constrained('annee_universitaires')->cascadeOnDelete();
            $table->date('date_inscription');
            $table->string('statut', 30)->default('EN_ATTENTE');
            $table->unique(['etudiant_id', 'formation_id', 'annee_universitaire_id'], 'insc_etud_form_annee_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};