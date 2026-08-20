<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            'Loom department',
            'Plant department',
            'BCS department',
            'Printing department',
            'Stitching department',
        ];

        foreach ($departments as $deptName) {
            $slug = Str::slug($deptName);
            
            Department::updateOrCreate(
                ['Slug' => $slug],
                [
                    'DepartmentName' => $deptName,
                    'IsActive' => 1,
                    'CreatedBy' => 1,
                    'UpdatedBy' => 1,
                ]
            );
        }
    }
}
