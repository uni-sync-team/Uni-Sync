<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultat_semestres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->cascadeOnDelete();
            $table->foreignId('semestre_id')->constrained('semestres')->cascadeOnDelete();
            $table->decimal('moyenne', 5, 2)->nullable();
            $table->string('decision', 30)->nullable();
            $table->unique(['etudiant_id', 'semestre_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultat_semestres');
    }
};