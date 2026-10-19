<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Token acak untuk alamat QR di invoice. Unik dan tidak pernah berubah.
            $table->string('public_token', 64)->nullable()->unique()->after('order_number');
        });

        // Beri token untuk pesanan yang sudah ada
        $ids = DB::table('orders')->whereNull('public_token')->pluck('id');

        foreach ($ids as $id) {
            DB::table('orders')
                ->where('id', $id)
                ->update(['public_token' => Str::random(40)]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};