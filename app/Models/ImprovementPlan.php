<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImprovementPlan extends Model
{
    protected $fillable = [
        'institution_id', 'analysis_report_id', 'title',
        'academic_year', 'axes', 'ai_narrative', 'status'
    ];

    protected $casts = [
        'axes' => 'array',
    ];

    public function institution() {
        return $this->belongsTo(Institution::class);
    }

    public function analysisReport() {
        return $this->belongsTo(AnalysisReport::class);
    }
}