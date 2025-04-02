<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PersonalDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_number',
        'surname',
        'other_names',
        'date_of_birth',
        'marital_status',
        'phone_number',
        'address',
        'state_of_origin',
        'local_government',
        'ethnic_group',
        'religion',
        'name_of_father',
        'father_state_of_origin',
        'father_place_of_birth',
        'mother_state_of_origin',
        'mother_place_of_birth',
        'applicant_occupation',
        'desired_study_cent',
        'working_experience',
        'has_paid',
        'gender',
        'application_date',
        'application_trxid',
        'application_reference',
        'couse_fee_date',
        'course_fee_reference',
        'course_paid',
        'has_admission',
        'matric_number',
        'course',
        'school',
        'olevel1',
        'olevel2',
        'email',
        'nin',
        'scratchcard_pin_1',
        'scratchcard_serial',
        'scratchcard_upload',
        'passport',
    ];

    public function studentDetail()
    {
        return $this->hasOne(StudentDetail::class, 'id', 'application_number');
    }
    public function educationalDetail()
    {
        return $this->hasOne(EducationalDetail::class, 'application_number', 'id');
    }
    public function bioRegistration()
    {
        return $this->hasOne(BioRegistration::class, 'application_number', 'id');
    }

    public static function generateMatricNumber($program, $centre)
    {
        // Initialize school code
        $courseCode = "UNKNOWN"; // Default value if no match is found

        // Determine the school code using if-else
        if (in_array($program, [
            "Primary Education Studies (Double Major)",
            "Early Childhood Care Education (Double Major)"
        ])) {
            $courseCode = "ED";
        } elseif (in_array($program, [
            "Mathematics / Geography",
            "Maths / Economics",
            "Maths / Biology",
            "Maths / Special Education",
            "Integrated Sciences (Double Major)",
            "Biology / Geography",
            "Maths / Computer Science",
            "PHE (Double Major)",
            "Biology / Special Education",
            "Biology / Inter Science"
        ])) {
            $courseCode = "SE";
        } elseif (in_array($program, [
            "Technical Education Double Major",
            "Electrical / Electronics",
            "Automobile",
            "Building",
            "Wood Work",
            "Metal Work"
        ])) {
            $courseCode = "TE";
        } elseif (in_array($program, [
            "Geography / History",
            "Geography / Economics",
            "Geography / Social Studies",
            "History / CRS",
            "History / Islamic Studies",
            "Social Studies / Economics",
            "Social Studies / CRS",
            "Social Studies / Islamic Studies",
            "Islamic Studies / Special Education",
            "Eco / Special Education",
            "CRS / Special Education",
            "History / Special Education"
        ])) {
            $courseCode = "AS";
        } elseif (in_array($program, [
            "English / History",
            "English / CRS",
            "English / Arabic",
            "English / Hausa",
            "English / Social Studies",
            "English / Islamic Studies",
            "Hausa / Islamic Studies",
            "Hausa / Arabic",
            "Hausa / Social Studies",
            "Arabic / Islamic Studies",
            "Arabic / Social Studies",
            "English / Special Education",
            "Hausa / Special Education"
        ])) {
            $courseCode = "LA";
        } elseif (in_array($program, [
            "Agricultural Science Education (Double Major)",
            "Home Economics (Double Major)",
            "Business Education (Double Major)"
        ])) {
            $courseCode = "VE";
        }

        // Log school code (for debugging)
        Log::debug('SCHOOL CODE', [$courseCode]);

        // Determine the centre code
        if ($centre == 'suleja') {
            $matCentre = 'SU';
        } elseif ($centre == 'Rijau') {
            $matCentre = 'RJ'; // Default or other centre code
        } elseif ($centre == 'Gulu') {
            $matCentre = 'GL'; // Default or other centre code
        } elseif ($centre == 'New Bussa') {
            $matCentre = 'NB'; // Default or other centre code
        } elseif ($centre == 'Mokwa') {
            $matCentre = 'MK'; // Default or other centre code
        } elseif ($centre == 'Kagara') {
            $matCentre = 'KG'; // Default or other centre code
        } elseif ($centre == 'Salka') {
            $matCentre = 'SL'; // Default or other centre code
        } elseif ($centre == 'Kontogora') {
            $matCentre = 'KT'; // Default or other centre code
        } elseif ($centre == 'Katcha') {
            $matCentre = 'KC'; // Default or other centre code
        } elseif ($centre == 'Doko') {
            $matCentre = 'DK'; // Default or other centre code
        }


        // Get the last matric number for this program
        $latestStudent = self::where('course', $program)
            ->latest('matric_number')
            ->first();

        // Extract the sequential part of the latest matric number
        if ($latestStudent) {
            $lastNumber = (int) substr($latestStudent->matric_number, -5);
        } else {
            $lastNumber = 10000; // Start from 10001 if no records exist
        }

        // Generate the new matric number
        $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        $year = date('y'); // Get the current year (e.g., 24)
        $newGenerated = '1' . $newNumber;
        return "{$matCentre}/{$courseCode}/{$year}/{$newGenerated}";
    }
}
