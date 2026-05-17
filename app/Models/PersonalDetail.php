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
        'fee_academic_session',
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
        return $this->hasOne(StudentDetail::class,  'application_number', 'id');
    }
    public function educationalDetail()
    {
        return $this->hasOne(EducationalDetail::class, 'application_number', 'id');
    }
    public function bioRegistration()
    {
        return $this->hasOne(BioRegistration::class, 'application_number', 'id');
    }

    public function clearanceRequests()
    {
        return $this->hasMany(ClearanceRequest::class, 'personal_detail_id');
    }

    /**
     * Intake year embedded in generated matric numbers.
     * Lifted to a constant so callers can override (e.g. via config) without
     * touching the generator body.
     */
    public const MATRIC_INTAKE_YEAR = '26';

    /**
     * Sentinel returned when the supplied study centre is not in the map.
     * Keeps generation deterministic and lets a unique index / audit catch it.
     */
    public const UNKNOWN_CENTRE_CODE = 'XX';

    /**
     * Programme → department-code mapping. Lists mirror the original
     * implementation; extracted to a map so adding programmes is a one-line
     * change and lookups stay O(n) across a small dataset.
     */
    private static array $courseCodeMap = [
        'ED' => [
            'Primary Education Studies (Double Major)',
            'Early Childhood Care Education (Double Major)',
        ],
        'SE' => [
            'Mathematics / Geography',
            'Maths / Economics',
            'Maths / Biology',
            'Maths / Special Education',
            'Integrated Sciences (Double Major)',
            'Biology / Geography',
            'Maths / Computer Science',
            'PHE (Double Major)',
            'Biology / Special Education',
            'Biology / Inter Science',
        ],
        'TE' => [
            'Technical Education Double Major',
            'Electrical / Electronics',
            'Automobile',
            'Building',
            'Wood Work',
            'Metal Work',
        ],
        'AS' => [
            'Geography / History',
            'Geography / Economics',
            'Geography / Social Studies',
            'History / CRS',
            'History / Islamic Studies',
            'Social Studies / Economics',
            'Social Studies / CRS',
            'Social Studies / Islamic Studies',
            'Islamic Studies / Special Education',
            'Eco / Special Education',
            'CRS / Special Education',
            'History / Special Education',
        ],
        'LA' => [
            'English / History',
            'English / CRS',
            'English / Arabic',
            'English / Hausa',
            'English / Social Studies',
            'English / Islamic Studies',
            'Hausa / Islamic Studies',
            'Hausa / Arabic',
            'Hausa / Social Studies',
            'Arabic / Islamic Studies',
            'Arabic / Social Studies',
            'English / Special Education',
            'Hausa / Special Education',
        ],
        'VE' => [
            'Agricultural Science Education (Double Major)',
            'Home Economics (Double Major)',
            'Business Education (Double Major)',
        ],
    ];

    /**
     * Centre-name → two-letter code map. Keys are lowercased so input is
     * matched case-insensitively after trim — fixes the original branch list
     * where any spacing/casing drift left $matCentre undefined.
     */
    private static array $centreCodeMap = [
        'suleja' => 'SU',
        'rijau' => 'RJ',
        'gulu' => 'GL',
        'new bussa' => 'NB',
        'mokwa' => 'MK',
        'kagara' => 'KG',
        'salka' => 'SL',
        'kontogora' => 'KT',
        'katcha' => 'KC',
        'doko' => 'DK',
        'gawu' => 'GW',
        'bida' => 'BD',
        'patigi' => 'PG',
        'pandogari' => 'PD',
        'agaie' => 'AG',
    ];

    public static function generateMatricNumber($program, $centre)
    {
        $courseCode = self::resolveCourseCode($program);
        $matCentre = self::resolveCentreCode($centre);
        $year = self::MATRIC_INTAKE_YEAR;

        Log::debug('generateMatricNumber inputs', [
            'program' => $program,
            'centre' => $centre,
            'course_code' => $courseCode,
            'centre_code' => $matCentre,
            'year' => $year,
        ]);

        $prefix = "{$matCentre}/{$courseCode}/{$year}/";

        // Scope the running counter by centre + department + intake year so
        // independent cohorts cannot poach each other's serials. Parse the
        // numeric tail with a regex instead of substr(-5) so any non-conforming
        // legacy rows are skipped rather than corrupting the next number.
        $existingTails = self::where('matric_number', 'like', $prefix . '%')
            ->pluck('matric_number');

        $lastNumber = 10000;
        foreach ($existingTails as $matric) {
            $tail = substr((string) $matric, strlen($prefix));
            if (!preg_match('/^1(\d{5})$/', $tail, $matches)) {
                continue;
            }
            $serial = (int) $matches[1];
            if ($serial > $lastNumber) {
                $lastNumber = $serial;
            }
        }

        do {
            $lastNumber++;
            $newGenerated = '1' . str_pad((string) $lastNumber, 5, '0', STR_PAD_LEFT);
            $newMatricNumber = $prefix . $newGenerated;
        } while (self::where('matric_number', $newMatricNumber)->exists());

        return $newMatricNumber;
    }

    private static function resolveCourseCode($program): string
    {
        foreach (self::$courseCodeMap as $code => $programs) {
            if (in_array($program, $programs, true)) {
                return $code;
            }
        }
        return 'UNKNOWN';
    }

    private static function resolveCentreCode($centre): string
    {
        $key = strtolower(trim((string) $centre));
        if ($key === '' || !isset(self::$centreCodeMap[$key])) {
            Log::warning('generateMatricNumber: unknown centre', ['centre' => $centre]);
            return self::UNKNOWN_CENTRE_CODE;
        }
        return self::$centreCodeMap[$key];
    }
}
