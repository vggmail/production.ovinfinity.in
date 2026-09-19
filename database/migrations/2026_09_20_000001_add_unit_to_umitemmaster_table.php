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
        if (Schema::hasTable('umitemmaster') && !Schema::hasColumn('umitemmaster', 'Unit')) {
            Schema::table('umitemmaster', function (Blueprint $table) {
                $table->string('Unit', 50)->default('Not required')->nullable()->after('MinQuantity');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('umitemmaster') && Schema::hasColumn('umitemmaster', 'Unit')) {
            Schema::table('umitemmaster', function (Blueprint $table) {
                $table->dropColumn('Unit');
            });
        }
    }
};
