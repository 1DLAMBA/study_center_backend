<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['application_id', 'course', 'course_code', 
                            'level_of_course','mode_of_course','fees_paid',
                            'subject_of_study', 
                            'session','fees_trx_id',
                            'semester','course_type',
                            'fees_reference','course_reg_date'];

    public function personalDetails()
    {
        return $this->belongsTo(PersonalDetail::class);
    }
}
