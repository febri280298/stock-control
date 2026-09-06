<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('po', function (Blueprint $table) {
            $table->unsignedBigInteger('approval_mkt1_by')->nullable()->after('status');
            $table->timestamp('approval_mkt1_at')->nullable()->after('approval_mkt1_by');

            $table->unsignedBigInteger('approval_pcd_by')->nullable()->after('approval_mkt1_at');
            $table->timestamp('approval_pcd_at')->nullable()->after('approval_pcd_by');

            $table->unsignedBigInteger('approval_mkt2_by')->nullable()->after('approval_pcd_at');
            $table->timestamp('approval_mkt2_at')->nullable()->after('approval_mkt2_by');

            $table->foreign('approval_mkt1_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approval_pcd_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approval_mkt2_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('po', function (Blueprint $table) {
            $table->dropForeign(['approval_mkt1_by']);
            $table->dropForeign(['approval_pcd_by']);
            $table->dropForeign(['approval_mkt2_by']);
            $table->dropColumn(['approval_mkt1_by', 'approval_mkt1_at', 'approval_pcd_by', 'approval_pcd_at', 'approval_mkt2_by', 'approval_mkt2_at']);
        });
    }
};
