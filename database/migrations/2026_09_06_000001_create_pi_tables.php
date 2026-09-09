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
        Schema::dropIfExists('inpichild');
        Schema::dropIfExists('inpi');

        // 1. PI Header Table
        Schema::create('inpi', function (Blueprint $table) {
            $table->id('ID');
            $table->string('InvoiceEntryNo', 100)->unique();
            $table->date('PIDate');
            $table->unsignedBigInteger('Supplier');
            $table->string('PINumber', 100)->unique();
            $table->text('MRLNumbers')->nullable();
            $table->integer('TotalItems')->default(0);
            $table->decimal('TotalQuantity', 12, 2)->default(0);
            $table->decimal('TotalAdPayDisAmount', 12, 2)->default(0);
            $table->decimal('TotalNetValAfterDis', 12, 2)->default(0);
            $table->decimal('TotalPackChargesAmount', 12, 2)->default(0);
            $table->decimal('TotalFreightAmount', 12, 2)->default(0);
            $table->decimal('TotalNetAmount', 12, 2)->default(0);
            $table->decimal('TotalGSTAmount', 12, 2)->default(0);
            $table->decimal('TotalPIAmount', 12, 2)->default(0);
            $table->text('Remarks')->nullable();
            $table->boolean('IsActive')->default(1);
            $table->unsignedBigInteger('CreatedBy')->nullable();
            $table->unsignedBigInteger('UpdatedBy')->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->timestamp('UpdatedOn')->nullable();

            $table->index('InvoiceEntryNo');
            $table->index('PIDate');
            $table->index('Supplier');
            $table->index('PINumber');
        });

        // 2. PI Child Table
        Schema::create('inpichild', function (Blueprint $table) {
            $table->id('ID');
            $table->unsignedBigInteger('PI');
            $table->unsignedBigInteger('MRLEntryChild')->nullable();
            $table->unsignedBigInteger('MRLEntry')->nullable();
            $table->unsignedBigInteger('ItemMaster');
            $table->decimal('Quantity', 12, 2)->default(0);
            $table->decimal('BasicRate', 12, 2)->default(0);
            $table->decimal('Amount', 12, 2)->default(0);
            $table->decimal('TradeDisPercent', 12, 2)->default(0);
            $table->decimal('TradeDisAmount', 12, 2)->default(0);
            $table->decimal('NetValAfterTrade', 12, 2)->default(0);
            $table->decimal('AdPayDisPercent', 12, 2)->default(0);
            $table->decimal('AdPayDisAmount', 12, 2)->default(0);
            $table->decimal('NetValAfterDis', 12, 2)->default(0);
            $table->decimal('PackChargesPercent', 12, 2)->default(0);
            $table->decimal('PackChargesAmount', 12, 2)->default(0);
            $table->decimal('FreightPercent', 12, 2)->default(0);
            $table->decimal('FreightAmount', 12, 2)->default(0);
            $table->decimal('NetAmount', 12, 2)->default(0);
            $table->decimal('GSTRate', 12, 2)->default(0);
            $table->decimal('GSTAmount', 12, 2)->default(0);
            $table->decimal('PIAmount', 12, 2)->default(0);
            $table->decimal('NetRate', 12, 2)->default(0);
            $table->boolean('IsActive')->default(1);
            $table->unsignedBigInteger('CreatedBy')->nullable();
            $table->unsignedBigInteger('UpdatedBy')->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->timestamp('UpdatedOn')->nullable();

            $table->foreign('PI')->references('ID')->on('inpi')->onDelete('cascade');
            $table->index('MRLEntryChild');
            $table->index('MRLEntry');
            $table->index('ItemMaster');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inpichild');
        Schema::dropIfExists('inpi');
    }
};
