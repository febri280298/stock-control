<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('po_item_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_item_id')->constrained('po_items')->onDelete('cascade');
            $table->unsignedInteger('batch_number'); // 1, 2, 3, dst — urutan gelombang pengiriman
            $table->integer('qty'); // qty yang direncanakan buat batch ini
            $table->integer('qty_delivered')->default(0); // qty yang udah kekirim (diisi otomatis via FIFO)
            $table->date('target_date')->nullable(); // estimasi tanggal delivery, informasi doang
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('po_item_batches');
    }
};
