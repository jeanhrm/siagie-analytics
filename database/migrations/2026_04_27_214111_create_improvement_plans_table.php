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
        Schema::create('improvement_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('analysis_report_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('academic_year');
            $table->json('axes'); // ejes del plan generados por Claude
            $table->longText('ai_narrative')->nullable(); // narrativa del plan
            $table->string('status')->default('draft'); // draft, active, completed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('improvement_plans');
    }
};
