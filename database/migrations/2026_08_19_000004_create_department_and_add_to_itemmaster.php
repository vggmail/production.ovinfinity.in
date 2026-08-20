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
        // 1. Create umdepartment table
        if (!Schema::hasTable('umdepartment')) {
            Schema::create('umdepartment', function (Blueprint $table) {
                $table->id('ID');
                $table->string('DepartmentName', 100);
                $table->string('Slug', 100)->unique();
                $table->boolean('IsActive')->default(1);
                $table->integer('CreatedBy')->nullable();
                $table->timestamp('CreatedOn')->useCurrent();
                $table->integer('UpdatedBy')->nullable();
                $table->timestamp('UpdatedOn')->useCurrent()->useCurrentOnUpdate();
            });
        }

        // 2. Add Department column to umitemmaster before HSNNo
        if (Schema::hasTable('umitemmaster') && !Schema::hasColumn('umitemmaster', 'Department')) {
            Schema::table('umitemmaster', function (Blueprint $table) {
                $table->unsignedBigInteger('Department')->nullable()->after('MinQuantity');
                $table->foreign('Department')->references('ID')->on('umdepartment')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('umitemmaster') && Schema::hasColumn('umitemmaster', 'Department')) {
            Schema::table('umitemmaster', function (Blueprint $table) {
                $table->dropForeign(['Department']);
                $table->dropColumn('Department');
            });
        }

        Schema::dropIfExists('umdepartment');
    }
};
