<?php

namespace App\Imports;

use App\Models\GraduationList;
use App\Support\CentreScope;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GraduationListImport implements ToModel, WithHeadingRow
{
    public function __construct(private readonly ?string $lockCentre = null)
    {
    }

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

        $centre = isset($row['centre']) ? trim((string) $row['centre']) : null;

        if ($this->lockCentre) {
            if (! CentreScope::matches($centre, $this->lockCentre)) {
                return null;
            }

            $existing = GraduationList::where('matric_number', $matric)->first();
            if ($existing && ! CentreScope::matches($existing->centre, $this->lockCentre)) {
                return null;
            }
        }

        GraduationList::updateOrCreate(
            ['matric_number' => $matric],
            [
                'name' => isset($row['name']) ? trim(preg_replace('/\s+/', ' ', (string) $row['name'])) : null,
                'course' => isset($row['course']) ? trim((string) $row['course']) : null,
                'centre' => $centre,
            ]
        );

        return null;
    }
}
