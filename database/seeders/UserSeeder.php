<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('auusercredentials')->updateOrInsert(
            ['UserName' => 'Production@Admin'],
            [
                'UserCode' => 'EMP101',
                'FullName' => 'Production Admin',
                'Password' => 'Pass@123$#',
                'ContactNo' => '9999999999',
                'EmailId' => 'info@Production.com',
                'Address' => 'Kolhapur',
                'City' => 'Kolhapur',
                'IsActive' => 1,
                'CreatedBy' => '1',
                'CreatedOn' => '2026-06-19 16:16:41',
                'UpdatedBy' => '1',
                'UpdatedOn' => '2026-06-19 16:16:41',
            ]
        );
    }
}
