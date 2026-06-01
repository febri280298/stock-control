<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts')->onDelete('cascade');
            $table->enum('type', ['masuk', 'keluar']);
            $table->integer('qty');
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('status_qc')->nullable();
            $table->string('keterangan')->nullable();
            $table->string('supplier')->nullable();   // untuk masuk
            $table->string('tujuan')->nullable();     // untuk keluar
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
