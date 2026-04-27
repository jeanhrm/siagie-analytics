<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'student_id', 'upload_id', 'subject',
        'score', 'period', 'status', 'academic_year'
    ];

    protected $casts = [
        'score' => 'decimal:1',
    ];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function upload() {
        return $this->belongsTo(Upload::class);
    }
}