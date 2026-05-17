<?php

namespace App\Imports;

use App\Models\PersonalDetail;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class StudentsImport implements ToModel, WithHeadingRow, WithStartRow
{
    private $currentCourse = null; 

    private $centre; // Add a property for centre

    public function startRow(): int
    {
        return 2; // Specify the starting row number
    }
    public function __construct($centre)
    {
        $this->centre = $centre; // Assign the centre value
    }

    public function model(array $row)
    {
        $name = isset($row['name']) ? trim((string) $row['name']) : '';
        $admNo = isset($row['adm_no']) ? trim((string) $row['adm_no']) : '';

        // Course-header rows: name present, adm_no blank/null. Empty-string
        // matters for CSV uploads where adm_no comes through as '' not null.
        if ($admNo === '') {
            if ($name !== '') {
                $this->currentCourse = $name;
            }
            return null;
        }

        if ($name === '' || $this->currentCourse === null) {
            return null;
        }

        // updateOrCreate keyed by matric_number keeps re-uploads idempotent:
        // running the same file twice updates the existing row instead of
        // creating a duplicate. application_number stays in lockstep with
        // matric_number so admin lookups by either field still resolve.
        PersonalDetail::updateOrCreate(
            ['matric_number' => $admNo],
            [
                'application_number' => $admNo,
                'other_names' => $name,
                'course' => $this->currentCourse,
                'desired_study_cent' => $this->centre,
                'has_admission' => true,
            ]
        );

        return null;
    }
}