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
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // código modular IE
            $table->string('ugel')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('region')->default('Huancavelica');
            $table->string('level')->nullable(); // primaria, secundaria
            $table->string('director_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
