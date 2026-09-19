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
            ['name' => 'Tape Plant', 'code' => 'TP'],
            ['name' => 'BCS', 'code' => 'BCS'],
            ['name' => 'Printing', 'code' => 'PRN'],
            ['name' => 'Stitching', 'code' => 'STCH'],
            ['name' => 'Office', 'code' => 'OFF'],
            ['name' => 'Compressor', 'code' => 'CMP'],
            ['name' => 'Bale Press', 'code' => 'BP'],
            ['name' => 'Electrical', 'code' => 'ELE'],
        ];

        $activeSlugs = [];
        foreach ($departments as $dept) {
            $slug = Str::slug($dept['name']);
            $activeSlugs[] = $slug;
            
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

        // Deactivate old departments not in the updated list
        Department::whereNotIn('Slug', $activeSlugs)->update(['IsActive' => 0]);
    }
}
