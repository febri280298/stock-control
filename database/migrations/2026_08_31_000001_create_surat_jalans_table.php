<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_jalans', function (Blueprint $table) {
            $table->id();
            $table->string('no_surat_jalan')->nullable();
            $table->string('delivery_to')->nullable();
            $table->date('date')->nullable();
            $table->string('project')->nullable();
            $table->string('no_po')->nullable();
            $table->json('transaction_ids');        // id transaksi apa aja yang masuk ke SJ ini
            $table->string('file_path');             // lokasi file PDF-nya disimpen (storage/app/...)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable(); // cache nama, biar tetep kebaca walau user-nya dihapus
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_jalans');
    }
};
