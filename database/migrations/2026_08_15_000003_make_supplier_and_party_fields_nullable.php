<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $fields = ['GSTIN', 'ContactNo', 'Address', 'Street', 'City', 'District', 'State', 'PinCode'];

        foreach ($fields as $field) {
            DB::statement("ALTER TABLE umsupplier MODIFY {$field} VARCHAR(255) NULL");
            DB::statement("ALTER TABLE umparty MODIFY {$field} VARCHAR(255) NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
