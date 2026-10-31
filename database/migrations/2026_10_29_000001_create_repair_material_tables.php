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
        // 1. Repair Issue Header
        if (!Schema::hasTable('inrepairissue')) {
            Schema::create('inrepairissue', function (Blueprint $table) {
                $table->id('ID');
                $table->string('RepairNo', 50)->unique();
                $table->date('RepairDate');
                $table->unsignedBigInteger('Supplier');
                $table->integer('TotalItems')->default(0);
                $table->decimal('TotalQuantity', 12, 2)->default(0);
                $table->decimal('TotalAmount', 12, 2)->default(0);
                $table->string('Status', 20)->default('Pending'); // 'Pending', 'Complete'
                $table->text('Remarks')->nullable();
                $table->boolean('IsActive')->default(1);
                $table->unsignedBigInteger('CreatedBy')->nullable();
                $table->unsignedBigInteger('UpdatedBy')->nullable();
                $table->timestamp('CreatedOn')->useCurrent();
                $table->timestamp('UpdatedOn')->useCurrent()->useCurrentOnUpdate();

                $table->index('Supplier');
                $table->index('RepairDate');
                $table->index('Status');
            });
        }

        // 2. Repair Issue Items
        if (!Schema::hasTable('inrepairissuechild')) {
            Schema::create('inrepairissuechild', function (Blueprint $table) {
                $table->id('ID');
                $table->unsignedBigInteger('RepairIssue');
                $table->unsignedBigInteger('ItemMaster');
                $table->decimal('Quantity', 12, 2)->default(0);
                $table->decimal('Rate', 12, 2)->default(0);
                $table->decimal('Amount', 12, 2)->default(0);
                $table->boolean('IsActive')->default(1);
                $table->unsignedBigInteger('CreatedBy')->nullable();
                $table->unsignedBigInteger('UpdatedBy')->nullable();
                $table->timestamp('CreatedOn')->useCurrent();
                $table->timestamp('UpdatedOn')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('RepairIssue')->references('ID')->on('inrepairissue')->onDelete('cascade');
                $table->index('ItemMaster');
            });
        }

        // 3. Repair Return Header
        if (!Schema::hasTable('inrepairreturn')) {
            Schema::create('inrepairreturn', function (Blueprint $table) {
                $table->id('ID');
                $table->string('ReturnNo', 50)->unique();
                $table->date('ReturnDate');
                $table->unsignedBigInteger('RepairIssue');
                $table->unsignedBigInteger('Supplier');
                $table->integer('TotalItems')->default(0);
                $table->decimal('TotalQuantity', 12, 2)->default(0);
                $table->decimal('TotalAmount', 12, 2)->default(0);
                $table->string('Status', 20)->default('Pending'); // 'Pending', 'Received'
                $table->text('Remarks')->nullable();
                $table->boolean('IsActive')->default(1);
                $table->unsignedBigInteger('CreatedBy')->nullable();
                $table->unsignedBigInteger('UpdatedBy')->nullable();
                $table->timestamp('CreatedOn')->useCurrent();
                $table->timestamp('UpdatedOn')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('RepairIssue')->references('ID')->on('inrepairissue')->onDelete('cascade');
                $table->index('Supplier');
                $table->index('ReturnDate');
                $table->index('Status');
            });
        }

        // 4. Repair Return Items
        if (!Schema::hasTable('inrepairreturnchild')) {
            Schema::create('inrepairreturnchild', function (Blueprint $table) {
                $table->id('ID');
                $table->unsignedBigInteger('RepairReturn');
                $table->unsignedBigInteger('RepairIssueChild')->nullable();
                $table->unsignedBigInteger('ItemMaster');
                $table->decimal('IssuedQuantity', 12, 2)->default(0);
                $table->decimal('Quantity', 12, 2)->default(0); // Returned Qty
                $table->decimal('Rate', 12, 2)->default(0);
                $table->decimal('Amount', 12, 2)->default(0);
                $table->boolean('IsActive')->default(1);
                $table->unsignedBigInteger('CreatedBy')->nullable();
                $table->unsignedBigInteger('UpdatedBy')->nullable();
                $table->timestamp('CreatedOn')->useCurrent();
                $table->timestamp('UpdatedOn')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('RepairReturn')->references('ID')->on('inrepairreturn')->onDelete('cascade');
                $table->index('RepairIssueChild');
                $table->index('ItemMaster');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inrepairreturnchild');
        Schema::dropIfExists('inrepairreturn');
        Schema::dropIfExists('inrepairissuechild');
        Schema::dropIfExists('inrepairissue');
    }
};
