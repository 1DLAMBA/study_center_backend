<?php

namespace App\Imports;

use App\Models\CourseData;
use Maatwebsite\Excel\Concerns\ToModel;

class CourseDataImport implements ToModel
{
    public function model(array $row)
    {
        return new CourseData([
            'course' => $row[1],       // Assuming the first column in Excel is 'course'
           ]);
    }
}
