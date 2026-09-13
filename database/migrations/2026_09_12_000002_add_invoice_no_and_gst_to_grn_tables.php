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
            if (!Schema::hasColumn('ingrn', 'InvoiceNo')) {
                $table->string('InvoiceNo', 100)->nullable()->after('GRNDate');
            }
            if (!Schema::hasColumn('ingrn', 'TotalAmount')) {
                $table->decimal('TotalAmount', 15, 2)->default(0)->after('PINumbers');
            }
            if (!Schema::hasColumn('ingrn', 'TotalGSTAmount')) {
                $table->decimal('TotalGSTAmount', 15, 2)->default(0)->after('TotalAmount');
            }
            if (!Schema::hasColumn('ingrn', 'GrandTotal')) {
                $table->decimal('GrandTotal', 15, 2)->default(0)->after('TotalGSTAmount');
            }
        });

        Schema::table('ingrnchild', function (Blueprint $table) {
            if (!Schema::hasColumn('ingrnchild', 'Rate')) {
                $table->decimal('Rate', 15, 2)->default(0)->after('Quantity');
            }
            if (!Schema::hasColumn('ingrnchild', 'Amount')) {
                $table->decimal('Amount', 15, 2)->default(0)->after('Rate');
            }
            if (!Schema::hasColumn('ingrnchild', 'GSTRate')) {
                $table->decimal('GSTRate', 8, 2)->default(0)->after('Amount');
            }
            if (!Schema::hasColumn('ingrnchild', 'GSTAmount')) {
                $table->decimal('GSTAmount', 15, 2)->default(0)->after('GSTRate');
            }
            if (!Schema::hasColumn('ingrnchild', 'TotalAmount')) {
                $table->decimal('TotalAmount', 15, 2)->default(0)->after('GSTAmount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingrn', function (Blueprint $table) {
            $columns = ['InvoiceNo', 'TotalAmount', 'TotalGSTAmount', 'GrandTotal'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('ingrn', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('ingrnchild', function (Blueprint $table) {
            $columns = ['Rate', 'Amount', 'GSTRate', 'GSTAmount', 'TotalAmount'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('ingrnchild', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
