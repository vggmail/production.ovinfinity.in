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
            ['name' => 'Loom Shed 1', 'code' => 'LS1'],
            ['name' => 'Loom Shed 2', 'code' => 'LS2'],
            ['name' => 'Folding', 'code' => 'FLD'],
            ['name' => 'Sizing', 'code' => 'SIZ'],
            ['name' => 'Warping', 'code' => 'WRP'],
            ['name' => 'Yarn Store', 'code' => 'YRN'],
            ['name' => 'Engineering / Maintenance', 'code' => 'ENG'],
            ['name' => 'Electrical', 'code' => 'ELE'],
            ['name' => 'Quality / Inspection', 'code' => 'QTY'],
            ['name' => 'Packing & Dispatch', 'code' => 'PACK'],
            ['name' => 'Admin / Office', 'code' => 'ADM'],
        ];

        foreach ($departments as $dept) {
            $slug = Str::slug($dept['name']);
            
            Department::updateOrCreate(
                ['Slug' => $slug],
                [
                    'DepartmentName' => $dept['name'],
                    'Code' => $dept['code'],
                    'IsActive' => 1,
                    'CreatedBy' => 1,
                    'UpdatedBy' => 1,
                ]
            );
        }
    }
}
