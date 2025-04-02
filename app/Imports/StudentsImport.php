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
        if (!isset($row['name']) || is_null($row['adm_no'])) {
            $this->currentCourse = $row['name']; // Update the current course
            return null; // Skip this row
        }
        $existingStudent = PersonalDetail::where('matric_number', $row['adm_no'])->first();

        if ($existingStudent) {
            // Update the existing record
            $existingStudent->update([
                'other_names' => $row['name'],
                'course' => $this->currentCourse,
                'desired_study_cent' => $this->centre,
                'has_admission' => true,
            ]);
            return null; // Skip creating a new instance
        }
    
        // Insert a new record if it doesn't exist
        return new PersonalDetail([
            'matric_number' => $row['adm_no'],
            'application_number' => $row['adm_no'],
            'other_names' => $row['name'],
            'course' => $this->currentCourse,
            'desired_study_cent' => $this->centre,
            'has_admission' => true,
        ]);

    }
}