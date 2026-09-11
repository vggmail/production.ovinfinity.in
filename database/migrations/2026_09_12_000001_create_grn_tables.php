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
        Schema::dropIfExists('ingrnchild');
        Schema::dropIfExists('ingrn');

        // 1. GRN Header Table
        Schema::create('ingrn', function (Blueprint $table) {
            $table->id('ID');
            $table->string('GRNNumber', 100)->unique();
            $table->date('GRNDate');
            $table->unsignedBigInteger('Supplier');
            $table->text('PINumbers')->nullable();
            $table->text('Remarks')->nullable();
            $table->boolean('IsActive')->default(1);
            $table->unsignedBigInteger('CreatedBy')->nullable();
            $table->unsignedBigInteger('UpdatedBy')->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->timestamp('UpdatedOn')->nullable();

            $table->index('GRNNumber');
            $table->index('GRNDate');
            $table->index('Supplier');
        });

        // 2. GRN Child Table
        Schema::create('ingrnchild', function (Blueprint $table) {
            $table->id('ID');
            $table->unsignedBigInteger('GRN');
            $table->unsignedBigInteger('PI');
            $table->unsignedBigInteger('PIChild');
            $table->unsignedBigInteger('ItemMaster');
            $table->decimal('Quantity', 12, 2)->default(0);
            $table->unsignedBigInteger('CreatedBy')->nullable();
            $table->unsignedBigInteger('UpdatedBy')->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->timestamp('UpdatedOn')->nullable();

            $table->foreign('GRN')->references('ID')->on('ingrn')->onDelete('cascade');
            $table->index('PI');
            $table->index('PIChild');
            $table->index('ItemMaster');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingrnchild');
        Schema::dropIfExists('ingrn');
    }
};
