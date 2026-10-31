<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('surat_jalans', function (Blueprint $table) {
            $table->date('returned_at')->nullable();                 // tanggal SJ kembali (dipilih manual)
            $table->unsignedBigInteger('returned_by')->nullable();   // user yang menandai
            $table->string('returned_by_name')->nullable();
        });
    }

    public function down()
    {
        Schema::table('surat_jalans', function (Blueprint $table) {
            $table->dropColumn(['returned_at', 'returned_by', 'returned_by_name']);
        });
    }
};
