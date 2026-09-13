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
        if (Schema::hasTable('umdepartment')) {
            Schema::table('umdepartment', function (Blueprint $table) {
                if (!Schema::hasColumn('umdepartment', 'Code')) {
                    $table->string('Code', 50)->nullable()->after('DepartmentName');
                }
                if (!Schema::hasColumn('umdepartment', 'Remarks')) {
                    $table->text('Remarks')->nullable()->after('Slug');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('umdepartment')) {
            Schema::table('umdepartment', function (Blueprint $table) {
                if (Schema::hasColumn('umdepartment', 'Remarks')) {
                    $table->dropColumn('Remarks');
                }
                if (Schema::hasColumn('umdepartment', 'Code')) {
                    $table->dropColumn('Code');
                }
            });
        }
    }
};
