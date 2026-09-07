<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable()->after('min_stock');
            $table->date('price_valid_from')->nullable()->after('price');
            $table->date('price_valid_until')->nullable()->after('price_valid_from');
            $table->decimal('tarikan_sales', 15, 2)->nullable()->after('price_valid_until');
        });
    }

    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropColumn(['price', 'price_valid_from', 'price_valid_until', 'tarikan_sales']);
        });
    }
};
