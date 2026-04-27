<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analysis_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('upload_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year');
            $table->json('summary_data'); // métricas clave en JSON
            $table->longText('ai_analysis'); // análisis narrativo de Claude
            $table->json('critical_areas')->nullable(); // áreas críticas identificadas
            $table->json('strengths')->nullable(); // fortalezas identificadas
            $table->json('at_risk_students')->nullable(); // lista de estudiantes en riesgo
            $table->string('status')->default('generated');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_reports');
    }
};
