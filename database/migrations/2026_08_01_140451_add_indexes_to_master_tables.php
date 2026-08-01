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
        Schema::table('umparty', function (Blueprint $table) {
            $table->index('PartyName');
            $table->index('GSTIN');
            $table->index('ContactNo');
        });

        Schema::table('umsupplier', function (Blueprint $table) {
            $table->index('SupplierName');
            $table->index('GSTIN');
            $table->index('ContactNo');
        });

        Schema::table('umrollsize', function (Blueprint $table) {
            $table->index('RollSize');
        });

        Schema::table('umloomnumber', function (Blueprint $table) {
            $table->index('LoomNumber');
        });

        Schema::table('umfabriccolor', function (Blueprint $table) {
            $table->index('FabricColor');
        });

        Schema::table('auusercredentials', function (Blueprint $table) {
            $table->index('UserName');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('umparty', function (Blueprint $table) {
            $table->dropIndex(['PartyName']);
            $table->dropIndex(['GSTIN']);
            $table->dropIndex(['ContactNo']);
        });

        Schema::table('umsupplier', function (Blueprint $table) {
            $table->dropIndex(['SupplierName']);
            $table->dropIndex(['GSTIN']);
            $table->dropIndex(['ContactNo']);
        });

        Schema::table('umrollsize', function (Blueprint $table) {
            $table->dropIndex(['RollSize']);
        });

        Schema::table('umloomnumber', function (Blueprint $table) {
            $table->dropIndex(['LoomNumber']);
        });

        Schema::table('umfabriccolor', function (Blueprint $table) {
            $table->dropIndex(['FabricColor']);
        });

        Schema::table('auusercredentials', function (Blueprint $table) {
            $table->dropIndex(['UserName']);
        });
    }
};
