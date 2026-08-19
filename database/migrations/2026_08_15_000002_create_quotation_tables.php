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
        Schema::dropIfExists('inquotationchild');
        Schema::dropIfExists('inquotation');

        // 1. Quotation Header Table
        Schema::create('inquotation', function (Blueprint $table) {
            $table->id('ID');
            $table->string('QuotationNumber', 100)->unique();
            $table->date('QuotationDate');
            $table->unsignedBigInteger('Supplier');
            $table->date('FromDate')->nullable();
            $table->date('ToDate')->nullable();
            $table->integer('TotalItems')->default(0);
            $table->decimal('TotalQuantity', 12, 2)->default(0);
            $table->text('Remarks')->nullable();
            $table->boolean('IsActive')->default(1);
            $table->unsignedBigInteger('CreatedBy')->nullable();
            $table->unsignedBigInteger('UpdatedBy')->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->timestamp('UpdatedOn')->nullable();

            $table->index('QuotationNumber');
            $table->index('QuotationDate');
            $table->index('Supplier');
        });

        // 2. Quotation Child Table
        Schema::create('inquotationchild', function (Blueprint $table) {
            $table->id('ID');
            $table->unsignedBigInteger('Quotation');
            $table->unsignedBigInteger('MRLEntryChild')->nullable();
            $table->unsignedBigInteger('ItemMaster');
            $table->decimal('Quantity', 12, 2)->default(0);
            $table->boolean('IsActive')->default(1);
            $table->unsignedBigInteger('CreatedBy')->nullable();
            $table->unsignedBigInteger('UpdatedBy')->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->timestamp('UpdatedOn')->nullable();

            $table->foreign('Quotation')->references('ID')->on('inquotation')->onDelete('cascade');
            $table->index('MRLEntryChild');
            $table->index('ItemMaster');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquotationchild');
        Schema::dropIfExists('inquotation');
    }
};
