<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('umitemmaster') && !Schema::hasColumn('umitemmaster', 'RackNo')) {
            Schema::table('umitemmaster', function (Blueprint $table) {
                $table->string('RackNo', 100)->nullable()->after('GSTPercentage');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('umitemmaster') && Schema::hasColumn('umitemmaster', 'RackNo')) {
            Schema::table('umitemmaster', function (Blueprint $table) {
                $table->dropColumn('RackNo');
            });
        }
    }
};
