<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semestre_id')->constrained('semestres')->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('intitule', 150);
            $table->integer('volume_horaire')->nullable();
            $table->decimal('coefficient', 4, 2)->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};