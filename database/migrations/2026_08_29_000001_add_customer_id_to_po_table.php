<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('po', function (Blueprint $table) {
            $table->string('customer_id')->nullable()->after('po_date');
        });
    }

    public function down()
    {
        Schema::table('po', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });
    }
};
