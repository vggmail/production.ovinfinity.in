<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intransaction', function (Blueprint $table) {
            $table->string('RollNumber', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('intransaction', function (Blueprint $table) {
            $table->integer('RollNumber')->nullable()->change();
        });
    }
};
