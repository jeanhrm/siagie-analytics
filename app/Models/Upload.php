<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = [
        'institution_id', 'filename', 'original_name',
        'type', 'academic_year', 'status',
        'total_rows', 'error_message'
    ];

    public function institution() {
        return $this->belongsTo(Institution::class);
    }

    public function students() {
        return $this->hasMany(Student::class);
    }

    public function grades() {
        return $this->hasMany(Grade::class);
    }

    public function analysisReport() {
        return $this->hasOne(AnalysisReport::class);
    }
}