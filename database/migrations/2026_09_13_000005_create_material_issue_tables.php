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
        if (!Schema::hasTable('inmaterialissue')) {
            Schema::create('inmaterialissue', function (Blueprint $table) {
                $table->id('ID');
                $table->string('IssueNo', 50)->unique();
                $table->date('IssueDate');
                $table->unsignedBigInteger('Technician')->nullable();
                $table->integer('TotalItems')->default(0);
                $table->decimal('TotalQuantity', 12, 2)->default(0);
                $table->text('Remarks')->nullable();
                $table->boolean('IsActive')->default(1);
                $table->integer('CreatedBy')->nullable();
                $table->timestamp('CreatedOn')->useCurrent();
                $table->integer('UpdatedBy')->nullable();
                $table->timestamp('UpdatedOn')->useCurrent()->useCurrentOnUpdate();

                $table->index('Technician');
                $table->index('IssueDate');
            });
        }

        if (!Schema::hasTable('inmaterialissuechild')) {
            Schema::create('inmaterialissuechild', function (Blueprint $table) {
                $table->id('ID');
                $table->unsignedBigInteger('MaterialIssue');
                $table->unsignedBigInteger('LoomNumber')->nullable();
                $table->unsignedBigInteger('Department')->nullable();
                $table->unsignedBigInteger('ItemMaster');
                $table->decimal('Quantity', 12, 2)->default(0);
                $table->boolean('IsActive')->default(1);
                $table->integer('CreatedBy')->nullable();
                $table->timestamp('CreatedOn')->useCurrent();
                $table->integer('UpdatedBy')->nullable();
                $table->timestamp('UpdatedOn')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('MaterialIssue')->references('ID')->on('inmaterialissue')->onDelete('cascade');
                $table->index('LoomNumber');
                $table->index('Department');
                $table->index('ItemMaster');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inmaterialissuechild');
        Schema::dropIfExists('inmaterialissue');
    }
};
