<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_number',
        'exam_type',
        'exam_number',
        'exam_month',
        'exam_year',
        'subject_1', 'grade_1',
        'subject_2', 'grade_2',
        'subject_3', 'grade_3',
        'subject_4', 'grade_4',
        'subject_5', 'grade_5',
        'subject_6', 'grade_6',
        'subject_7', 'grade_7',
        'subject_8', 'grade_8',
        'subject_9', 'grade_9',
        'uploaded_ssce',
    ];

    // Relationship with PersonalDetails
    public function personalDetail()
    {
        return $this->belongsTo(PersonalDetail::class, 'id', 'application_number');
    }
}
