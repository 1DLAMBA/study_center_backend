<?php

namespace App\Services;

use App\Models\ClearanceDepartment;

class ClearanceDepartmentService
{
    public function list()
    {
        return ClearanceDepartment::orderBy('name')->get();
    }

    public function create(array $data): ClearanceDepartment
    {
        return ClearanceDepartment::create([
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(ClearanceDepartment $department, array $data): ClearanceDepartment
    {
        $department->update([
            'name' => $data['name'] ?? $department->name,
            'is_active' => $data['is_active'] ?? $department->is_active,
        ]);

        return $department;
    }

    public function delete(ClearanceDepartment $department): void
    {
        $department->delete();
    }
}
