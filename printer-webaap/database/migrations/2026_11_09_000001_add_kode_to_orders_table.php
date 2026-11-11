<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode order singkat yang dibaca manusia (mis. "A7K2QX").
     *
     * Ditampilkan di halaman konfirmasi pembayaran dan dipakai kasir untuk
     * mencari order berdasarkan kode. Order lama ikut diisi kode baru.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('kode', 8)->nullable()->unique('uq_orders_kode')->after('id');
        });

        $alfabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $terpakai = [];

        foreach (DB::table('orders')->select('id')->get() as $order) {
            do {
                $kode = '';

                for ($i = 0; $i < 6; $i++) {
                    $kode .= $alfabet[random_int(0, strlen($alfabet) - 1)];
                }
            } while (isset($terpakai[$kode]) || Order::where('kode', $kode)->exists());

            $terpakai[$kode] = true;

            DB::table('orders')->where('id', $order->id)->update(['kode' => $kode]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('uq_orders_kode');
            $table->dropColumn('kode');
        });
    }
};
