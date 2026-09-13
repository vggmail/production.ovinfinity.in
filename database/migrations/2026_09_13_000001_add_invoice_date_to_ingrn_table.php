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
        Schema::table('ingrn', function (Blueprint $table) {
            if (!Schema::hasColumn('ingrn', 'InvoiceDate')) {
                $table->date('InvoiceDate')->nullable()->after('InvoiceNo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingrn', function (Blueprint $table) {
            if (Schema::hasColumn('ingrn', 'InvoiceDate')) {
                $table->dropColumn('InvoiceDate');
            }
        });
    }
};
