<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intransaction', function (Blueprint $table) {
            if (!Schema::hasColumn('intransaction', 'InvoiceNo')) {
                $table->string('InvoiceNo', 100)->nullable()->after('EntryDate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('intransaction', function (Blueprint $table) {
            if (Schema::hasColumn('intransaction', 'InvoiceNo')) {
                $table->dropColumn('InvoiceNo');
            }
        });
    }
};
