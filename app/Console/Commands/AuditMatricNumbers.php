<?php

namespace App\Console\Commands;

use App\Models\PersonalDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only audit for the matric number column. Surfaces the three failure
 * modes that the legacy generator and bulk imports can leave behind so the
 * registry team can review before any data repair script runs.
 */
class AuditMatricNumbers extends Command
{
    protected $signature = 'matric:audit
                            {--limit=50 : Maximum rows to display per section}';

    protected $description = 'Report duplicate matric numbers, malformed patterns, and UNKNOWN department codes.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $this->reportDuplicates($limit);
        $this->reportMalformed($limit);
        $this->reportUnknownDepartment($limit);

        return self::SUCCESS;
    }

    private function reportDuplicates(int $limit): void
    {
        $this->info('Duplicate matric_number values:');

        $duplicates = PersonalDetail::query()
            ->select('matric_number', DB::raw('COUNT(*) as occurrences'))
            ->whereNotNull('matric_number')
            ->where('matric_number', '!=', '')
            ->groupBy('matric_number')
            ->having('occurrences', '>', 1)
            ->orderByDesc('occurrences')
            ->limit($limit)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->line('  none');
            return;
        }

        $this->table(
            ['matric_number', 'occurrences'],
            $duplicates->map(fn ($row) => [$row->matric_number, $row->occurrences])->all()
        );
    }

    private function reportMalformed(int $limit): void
    {
        $this->info('Matric numbers not matching CENTRE/DEPT/YY/serial:');

        // Canonical format: 2-letter centre, 2-letter dept, 2-digit year, 6-digit serial starting with 1.
        $pattern = '/^[A-Z]{2}\/[A-Z]{2}\/\d{2}\/1\d{5}$/';

        $rows = PersonalDetail::query()
            ->whereNotNull('matric_number')
            ->where('matric_number', '!=', '')
            ->orderBy('id')
            ->cursor()
            ->reject(fn (PersonalDetail $row) => (bool) preg_match($pattern, (string) $row->matric_number))
            ->take($limit)
            ->values();

        if ($rows->isEmpty()) {
            $this->line('  none');
            return;
        }

        $this->table(
            ['id', 'matric_number', 'course', 'desired_study_cent'],
            $rows->map(fn ($row) => [
                $row->id,
                $row->matric_number,
                $row->course,
                $row->desired_study_cent,
            ])->all()
        );
    }

    private function reportUnknownDepartment(int $limit): void
    {
        $this->info('Matric numbers with UNKNOWN department code:');

        $rows = PersonalDetail::query()
            ->where('matric_number', 'like', '%/UNKNOWN/%')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'matric_number', 'course', 'desired_study_cent']);

        if ($rows->isEmpty()) {
            $this->line('  none');
            return;
        }

        $this->table(
            ['id', 'matric_number', 'course', 'desired_study_cent'],
            $rows->map(fn ($row) => [
                $row->id,
                $row->matric_number,
                $row->course,
                $row->desired_study_cent,
            ])->all()
        );
    }
}
