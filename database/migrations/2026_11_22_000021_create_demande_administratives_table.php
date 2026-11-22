<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demande_administratives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->cascadeOnDelete();
            $table->foreignId('administrateur_id')->nullable()->constrained('administrateurs')->nullOnDelete();
            $table->string('type_demande', 100);
            $table->dateTime('date_demande')->useCurrent();
            $table->string('statut', 30)->default('EN_ATTENTE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demande_administratives');
    }
};