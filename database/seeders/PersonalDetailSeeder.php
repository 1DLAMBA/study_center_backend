<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PersonalDetail;
use App\Models\StudentDetail;
use App\Models\EducationalDetail;
use Faker\Factory as Faker;

class PersonalDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Generate 50 random PersonalDetail records
        for ($i = 0; $i < 50; $i++) {
            $personalDetail = PersonalDetail::create([
                'application_number' => $faker->unique()->randomNumber(8),
                'surname' => $faker->lastName,
                'other_names' => $faker->firstName,
                'date_of_birth' => $faker->date,
                'marital_status' => $faker->randomElement(['Single', 'Married', 'Divorced']),
                'phone_number' => $faker->phoneNumber,
                'address' => $faker->address,
                'state_of_origin' => $faker->state,
                'local_government' => $faker->city,
                'ethnic_group' => $faker->word,
                'religion' => $faker->randomElement(['Christianity', 'Islam', 'Traditional']),
                'name_of_father' => $faker->name('male'),
                'father_state_of_origin' => $faker->state,
                'father_place_of_birth' => $faker->city,
                'mother_state_of_origin' => $faker->state,
                'mother_place_of_birth' => $faker->city,
                'applicant_occupation' => $faker->jobTitle,
                'desired_study_cent' => $faker->company,
                'working_experience' => $faker->sentence,
                'has_paid' => $faker->boolean,
                'gender' => $faker->randomElement(['Male', 'Female']),
                'application_date' => $faker->date,
                'application_trxid' => $faker->uuid,
                'application_reference' => $faker->uuid,
                'course_fee_date' => $faker->date,
                'course_fee_reference' => $faker->date,
                'course_paid' => $faker->boolean,
                'has_admission' => $faker->boolean,
                'matric_number' => $faker->unique()->randomNumber(8),
                'course' => $faker->word,
                'school' => $faker->company,
                'olevel1' => $faker->word,
                'olevel2' => $faker->word,
                'email' => $faker->unique()->safeEmail,
                'nin' => $faker->unique()->randomNumber(9),
                'scratchcard_pin_1' => $faker->randomNumber(6),
                'scratchcard_serial' => $faker->randomNumber(6),
                'scratchcard_upload' => $faker->word,
                'passport' => $faker->imageUrl(200, 200, 'people'),
            ]);

            // Create related StudentDetail record
            $studentDetail = StudentDetail::create([
                'application_number' => $personalDetail->application_number,
                'first_school' => $faker->company,
                'first_course' => $faker->word,
                'p_school_name_1' => $faker->company,
                'p_school_from_1' => $faker->date,
                'p_school_to_1' => $faker->date,
                'p_school_name_2' => $faker->company,
                'p_school_from_2' => $faker->date,
                'p_school_to_2' => $faker->date,
                's_school_name_1' => $faker->company,
                's_school_from_1' => $faker->date,
                's_school_to_1' => $faker->date,
                's_school_name_2' => $faker->company,
                's_school_from_2' => $faker->date,
                's_school_to_2' => $faker->date,
                'second_school' => $faker->company,
                'second_course' => $faker->word,
            ]);

            // Create related EducationalDetail record
            $educationalDetail = EducationalDetail::create([
                'application_number' => $personalDetail->application_number,
                'exam_type' => $faker->randomElement(['WAEC', 'NECO', 'GCE']),
                'exam_number' => $faker->unique()->randomNumber(8),
                'exam_month' => $faker->monthName,
                'exam_year' => $faker->year,
                'subject_1' => $faker->word,
                'grade_1' => $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
                'subject_2' => $faker->word,
                'grade_2' => $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
                'subject_3' => $faker->word,
                'grade_3' => $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
                'subject_4' => $faker->word,
                'grade_4' => $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
                'subject_5' => $faker->word,
                'grade_5' => $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
                'subject_6' => $faker->word,
                'grade_6' => $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
                'subject_7' => $faker->word,
                'grade_7' => $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
                'subject_8' => $faker->word,
                'grade_8' => $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
                'subject_9' => $faker->word,
                'grade_9' => $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
                'uploaded_ssce' => $faker->word,
            ]);
        }
    }
}