<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Kolom ini sudah dipakai Po::$fillable dan form Input PO, tapi migration-nya
// belum pernah dibuat - akibatnya penyimpanan PO gagal dengan
// "Unknown column 'project'" begitu sampai di server.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po', function (Blueprint $table) {
            if (! Schema::hasColumn('po', 'project')) {
                $table->string('project')->nullable()->after('customer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('po', function (Blueprint $table) {
            $table->dropColumn('project');
        });
    }
};
