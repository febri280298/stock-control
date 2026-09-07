<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('po', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->date('po_date');
            $table->date('target_delivery')->nullable();
            $table->enum('status', ['open', 'partial', 'closed'])->default('open');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('po_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('po')->onDelete('cascade');
            $table->foreignId('part_id')->constrained('parts');
            $table->integer('qty_order');
            $table->integer('qty_delivered')->default(0);
            $table->enum('status', ['open', 'partial', 'closed'])->default('open');
            $table->timestamps();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('kategori_keluar', ['po', 'non_po'])->nullable()->after('type');
            $table->foreignId('po_item_id')->nullable()->after('kategori_keluar')->constrained('po_items');
            $table->string('keterangan_non_po')->nullable()->after('po_item_id');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['po_item_id']);
            $table->dropColumn(['kategori_keluar', 'po_item_id', 'keterangan_non_po']);
        });
        Schema::dropIfExists('po_items');
        Schema::dropIfExists('po');
    }
};
