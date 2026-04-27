<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'institution_id', 'upload_id', 'dni', 'full_name',
        'grade', 'section', 'level', 'academic_year', 'status'
    ];

    public function institution() {
        return $this->belongsTo(Institution::class);
    }

    public function upload() {
        return $this->belongsTo(Upload::class);
    }

    public function grades() {
        return $this->hasMany(Grade::class);
    }

    public function averageScore(): float
    {
        return $this->grades()->avg('score') ?? 0;
    }

    public function isAtRisk(): bool
    {
        return $this->averageScore() < 11;
    }
}