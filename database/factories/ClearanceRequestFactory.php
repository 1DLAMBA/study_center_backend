<?php

namespace Database\Factories;

use App\Models\ClearanceRequest;
use App\Models\PersonalDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClearanceRequest>
 */
class ClearanceRequestFactory extends Factory
{
    protected $model = ClearanceRequest::class;

    public function definition(): array
    {
        $personalDetail = PersonalDetail::query()->inRandomOrder()->first();
        if (!$personalDetail) {
            $personalDetail = PersonalDetail::create($this->personalDetailPayload());
        }

        $status = $this->faker->randomElement([
            ClearanceRequest::STATUS_PENDING,
            ClearanceRequest::STATUS_APPROVED,
            ClearanceRequest::STATUS_REJECTED,
        ]);

        return [
            'personal_detail_id' => $personalDetail->id,
            'matric_number' => $personalDetail->matric_number ?? $this->fakeMatricNumber(),
            'status' => $status,
            'fees_receipt_path' => null,
            'rejection_reason' => $status === ClearanceRequest::STATUS_REJECTED ? $this->faker->sentence() : null,
            'approved_at' => $status === ClearanceRequest::STATUS_APPROVED ? now() : null,
            'rejected_at' => $status === ClearanceRequest::STATUS_REJECTED ? now() : null,
            'acceptance_paid' => $status === ClearanceRequest::STATUS_APPROVED ? $this->faker->boolean(60) : false,
            'acceptance_reference' => $status === ClearanceRequest::STATUS_APPROVED ? $this->faker->uuid() : null,
            'acceptance_paid_at' => $status === ClearanceRequest::STATUS_APPROVED ? now() : null,
        ];
    }

    private function personalDetailPayload(): array
    {
        $centreCodes = ['KT', 'SU', 'NB', 'MK', 'KG', 'SL', 'RJ', 'GL', 'BD', 'PG'];
        $courseCodes = ['SE', 'ED', 'TE', 'AS', 'LA', 'VE'];
        $centre = $this->faker->randomElement($centreCodes);
        $course = $this->faker->randomElement($courseCodes);

        return [
            'application_number' => (string) $this->faker->unique()->numberBetween(10000000, 99999999),
            'surname' => $this->faker->lastName,
            'other_names' => $this->faker->firstName,
            'date_of_birth' => $this->faker->date(),
            'marital_status' => $this->faker->randomElement(['Single', 'Married']),
            'phone_number' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'state_of_origin' => $this->faker->state,
            'local_government' => $this->faker->city,
            'ethnic_group' => $this->faker->word,
            'religion' => $this->faker->randomElement(['Christianity', 'Islam']),
            'name_of_father' => $this->faker->name('male'),
            'father_state_of_origin' => $this->faker->state,
            'father_place_of_birth' => $this->faker->city,
            'mother_state_of_origin' => $this->faker->state,
            'mother_place_of_birth' => $this->faker->city,
            'applicant_occupation' => $this->faker->jobTitle,
            'desired_study_cent' => $centre,
            'working_experience' => $this->faker->sentence,
            'has_paid' => true,
            'course_paid' => true,
            'matric_number' => "{$centre}/{$course}/26/1" . $this->faker->numberBetween(10000, 99999),
            'course' => $this->faker->word,
            'school' => $this->faker->company,
            'email' => $this->faker->unique()->safeEmail,
        ];
    }

    private function fakeMatricNumber(): string
    {
        $prefix = $this->faker->boolean(50) ? $this->faker->randomElement(['KT', 'SU', 'NB']) . '/' : '';
        $course = $this->faker->randomElement(['SE', 'ED', 'TE', 'AS', 'LA', 'VE']);
        $year = $this->faker->numberBetween(22, 26);
        $sequence = $this->faker->numberBetween(100000, 999999);

        return "{$prefix}{$course}/{$year}/{$sequence}";
    }
}
