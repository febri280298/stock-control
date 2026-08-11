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
    Schema::table('transactions', function (Blueprint $table) {
        $table->integer('qty_ok')->nullable()->after('qty');
        $table->string('keterangan_reject')->nullable()->after('keterangan');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    Schema::table('transactions', function (Blueprint $table) {
        $table->dropColumn(['qty_ok', 'keterangan_reject']);
    });
    }
};
