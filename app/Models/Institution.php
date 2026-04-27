<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    protected $fillable = [
        'name', 'code', 'ugel', 'district',
        'province', 'region', 'level', 'director_name'
    ];

    public function uploads() {
        return $this->hasMany(Upload::class);
    }

    public function students() {
        return $this->hasMany(Student::class);
    }

    public function analysisReports() {
        return $this->hasMany(AnalysisReport::class);
    }

    public function improvementPlans() {
        return $this->hasMany(ImprovementPlan::class);
    }
}