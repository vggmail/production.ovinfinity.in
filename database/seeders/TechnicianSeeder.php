<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Technician;

class TechnicianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $technicians = [
            'Rajesh Pal',
            'Udayraj Varma',
            'Sandeep Paswan',
            'Vijay Pal',
            'Kailash Jadhav',
            'Shivaji Surase',
            'Ajay Morya',
            'Indrajeet Varma',
            'Dharmendra',
        ];

        foreach ($technicians as $name) {
            Technician::updateOrCreate(
                ['Name' => $name],
                [
                    'IsActive' => 1,
                    'CreatedBy' => 1,
                    'UpdatedBy' => 1,
                ]
            );
        }
    }
}
