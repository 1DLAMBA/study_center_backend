<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'full_name',
        'phone_number',
        'reference',
        'passport',
        'has_paid',
        'programme',
        'application_number',
        'fees_date',
        'fees_reference',
        'payment_date',
    ];

    public function bioData()
{
    return $this->hasOne(BioData::class);
}
public function courses()
    {
        return $this->hasMany(Course::class);
    }

}
