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
        if (!Schema::hasTable('umtechnician')) {
            Schema::create('umtechnician', function (Blueprint $table) {
                $table->id('ID');
                $table->string('Name', 100);
                $table->string('Code', 50)->nullable();
                $table->string('Phone', 20)->nullable();
                $table->boolean('IsActive')->default(1);
                $table->integer('CreatedBy')->nullable();
                $table->timestamp('CreatedOn')->useCurrent();
                $table->integer('UpdatedBy')->nullable();
                $table->timestamp('UpdatedOn')->useCurrent()->useCurrentOnUpdate();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('umtechnician');
    }
};
