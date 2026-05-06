<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisReport extends Model
{
    protected $fillable = [
        'institution_id', 'upload_id', 'academic_year',
        'summary_data', 'ai_analysis', 'critical_areas',
        'strengths', 'at_risk_students', 'status', 'type'
    ];

    protected $casts = [
        'summary_data'      => 'array',
        'critical_areas'    => 'array',
        'strengths'         => 'array',
        'at_risk_students'  => 'array',
    ];

    public function institution() {
        return $this->belongsTo(Institution::class);
    }

    public function upload() {
        return $this->belongsTo(Upload::class);
    }

    public function improvementPlan() {
        return $this->hasOne(ImprovementPlan::class);
    }
}