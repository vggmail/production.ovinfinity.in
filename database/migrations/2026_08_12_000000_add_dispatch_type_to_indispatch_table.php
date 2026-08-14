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
        Schema::table('indispatch', function (Blueprint $table) {
            if (!Schema::hasColumn('indispatch', 'DispatchType')) {
                $table->string('DispatchType', 20)->default('Dispatch')->nullable()->after('InvoiceNumber');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indispatch', function (Blueprint $table) {
            if (Schema::hasColumn('indispatch', 'DispatchType')) {
                $table->dropColumn('DispatchType');
            }
        });
    }
};
