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
        Schema::dropIfExists('inmrlentrychild');
        Schema::dropIfExists('inmrlentry');
        Schema::dropIfExists('umitemmaster');

        // 1. Item Master Table
        Schema::create('umitemmaster', function (Blueprint $table) {
            $table->id('ID');
            $table->string('ItemName', 255);
            $table->string('PartNo', 100)->nullable();
            $table->string('CatalogueNo', 100)->nullable();
            $table->decimal('MinQuantity', 12, 2)->default(0);
            $table->string('HSNNo', 50)->nullable();
            $table->decimal('GSTPercentage', 5, 2)->default(0);
            $table->boolean('IsActive')->default(1);
            $table->unsignedBigInteger('CreatedBy')->nullable();
            $table->unsignedBigInteger('UpdatedBy')->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->timestamp('UpdatedOn')->nullable();

            $table->index('ItemName');
            $table->index('PartNo');
        });

        // 2. MRL Entry Header Table
        Schema::create('inmrlentry', function (Blueprint $table) {
            $table->id('ID');
            $table->date('EntryDate');
            $table->integer('TotalItems')->default(0);
            $table->decimal('TotalQuantity', 12, 2)->default(0);
            $table->boolean('IsActive')->default(1);
            $table->unsignedBigInteger('CreatedBy')->nullable();
            $table->unsignedBigInteger('UpdatedBy')->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->timestamp('UpdatedOn')->nullable();

            $table->index('EntryDate');
        });

        // 3. MRL Entry Child Table
        Schema::create('inmrlentrychild', function (Blueprint $table) {
            $table->id('ID');
            $table->unsignedBigInteger('MRLEntry');
            $table->unsignedBigInteger('ItemMaster');
            $table->decimal('Quantity', 12, 2)->default(0);
            $table->boolean('IsActive')->default(1);
            $table->unsignedBigInteger('CreatedBy')->nullable();
            $table->unsignedBigInteger('UpdatedBy')->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->timestamp('UpdatedOn')->nullable();

            $table->foreign('MRLEntry')->references('ID')->on('inmrlentry')->onDelete('cascade');
            $table->foreign('ItemMaster')->references('ID')->on('umitemmaster')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inmrlentrychild');
        Schema::dropIfExists('inmrlentry');
        Schema::dropIfExists('umitemmaster');
    }
};
