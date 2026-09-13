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
        Schema::table('umloomnumber', function (Blueprint $table) {
            if (!Schema::hasColumn('umloomnumber', 'MachineName')) {
                $table->string('MachineName', 100)->nullable()->after('LoomNumber');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('umloomnumber', function (Blueprint $table) {
            if (Schema::hasColumn('umloomnumber', 'MachineName')) {
                $table->dropColumn('MachineName');
            }
        });
    }
};
