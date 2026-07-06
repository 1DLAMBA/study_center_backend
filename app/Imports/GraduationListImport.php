<?php

namespace App\Imports;

use App\Models\GraduationList;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GraduationListImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Heading row "MATRIC NO / NAME / COURSE / CENTRE" normalises to
        // matric_no / name / course / centre.
        $matric = GraduationList::normalizeMatric(
            isset($row['matric_no']) ? (string) $row['matric_no'] : null
        );

        if ($matric === null) {
            return null;
        }

        // updateOrCreate keyed by matric_number keeps re-uploads and batched
        // uploads idempotent.
        GraduationList::updateOrCreate(
            ['matric_number' => $matric],
            [
                'name' => isset($row['name']) ? trim(preg_replace('/\s+/', ' ', (string) $row['name'])) : null,
                'course' => isset($row['course']) ? trim((string) $row['course']) : null,
                'centre' => isset($row['centre']) ? trim((string) $row['centre']) : null,
            ]
        );

        return null;
    }
}
