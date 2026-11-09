<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Order;
use App\Service\AntreanCetak;
use App\Service\KedaluwarsaOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Halaman kasir di dashboard: daftar order "Bayar di Kasir" yang sedang
 * menunggu konfirmasi. Kasir bisa mencari berdasarkan nama pelanggan, kode
 * order, atau id order, lalu menandai pembayaran berhasil atau dibatalkan.
 */
class KasirController extends Controller
{
    public function __construct(
        private readonly KedaluwarsaOrder $kedaluwarsa,
        private readonly AntreanCetak $antrean,
    ) {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $query = DB::table('orders')
            ->leftJoin('user', 'user.sessionid', '=', 'orders.session_id')
            ->where('orders.metode_pembayaran', 'kasir')
            ->where('orders.status_pembayaran', 'processing')
            ->select([
                'orders.id',
                'orders.kode',
                'orders.tanggal_pesan',
                'orders.tanggal_bayar',
                'orders.total_pembayaran',
                'user.name as nama_pelanggan',
                'user.no_wa',
            ]);

        // Pencarian kasir: nama pelanggan, kode order, atau id order.
        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($q) use ($like, $search) {
                $q->where('user.name', 'like', $like)
                    ->orWhere('orders.kode', 'like', $like)
                    ->orWhereRaw('LOWER(HEX(orders.id)) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        $jumlahMenunggu = (clone $query)->count();
        $totalMenunggu = (int) (clone $query)->sum('orders.total_pembayaran');

        $orders = $query->orderByDesc('orders.tanggal_bayar')
            ->paginate(10)
            ->withQueryString();

        $ringkasan = $this->ringkasanFile($orders->pluck('id')->all());

        $orders->setCollection(
            $orders->getCollection()->map(fn ($order) => $this->baris($order, $ringkasan))
        );

        return view('Dashboard.selection.kasir', compact(
            'orders',
            'search',
            'jumlahMenunggu',
            'totalMenunggu',
        ));
    }

    /**
     * Tandai order lunas: total final dicatat, lalu file masuk antrean cetak.
     */
    public function lunas(Request $request, string $id)
    {
        $validated = $request->validate([
            'total_dibayar' => ['required', 'integer', 'min:0'],
        ], [
            'total_dibayar.required' => 'Total yang dibayar wajib diisi.',
            'total_dibayar.integer' => 'Total yang dibayar harus berupa angka.',
            'total_dibayar.min' => 'Total yang dibayar tidak boleh negatif.',
        ]);

        $order = $this->cariOrder($id);

        if ($order === null) {
            return back()->with('error', 'Order tidak ditemukan.');
        }

        $total = (int) $validated['total_dibayar'];

        // Klaim atomik: hanya order kasir yang masih menunggu yang berubah.
        $diubah = Order::whereKey($order->id)
            ->where('metode_pembayaran', 'kasir')
            ->whereIn('status_pembayaran', ['processing', 'pending'])
            ->update([
                'status_pembayaran' => 'paid',
                'total_pembayaran' => $total,
                'total_dibayar' => $total,
            ]);

        if ($diubah === 0) {
            return back()->with('error', 'Order ini sudah diproses sebelumnya. Muat ulang halaman kasir.');
        }

        $antrean = $this->antrean->untukOrder($order->refresh());
        $kode = $this->kodeTampil($order);

        $pesan = 'Pembayaran order ' . $kode . ' dicatat lunas.';

        if ($antrean['gagal'] > 0) {
            return back()->with(
                'warning',
                $pesan . ' Namun ' . $antrean['gagal'] . ' file gagal masuk antrean cetak (komputer tidak tersedia). Periksa menu Komputer.'
            );
        }

        if ($antrean['berhasil'] === 0 && $antrean['dilewati'] > 0) {
            return back()->with(
                'success',
                $pesan . ' File sudah ada di antrean sebelumnya, jadi tidak digandakan.'
            );
        }

        return back()->with('success', $pesan . ' File sudah masuk antrean cetak.');
    }

    /**
     * Batalkan pembayaran: order menjadi gagal dan file pesanan dihapus.
     */
    public function batal(string $id)
    {
        $order = $this->cariOrder($id);

        if ($order === null) {
            return back()->with('error', 'Order tidak ditemukan.');
        }

        $diubah = $this->kedaluwarsa->batalkan($order);

        if (! $diubah) {
            return back()->with('error', 'Order ini sudah diproses sebelumnya. Muat ulang halaman kasir.');
        }

        $kode = $this->kodeTampil($order);

        return back()->with(
            'success',
            'Pembayaran order ' . $kode . ' dibatalkan. Pesanan dianggap gagal dan file dihapus.'
        );
    }

    private function cariOrder(string $id): ?Order
    {
        if (!ctype_xdigit($id) || strlen($id) !== 32) {
            return null;
        }

        return Order::find(hex2bin($id));
    }

    /**
     * Ringkasan file per order (jumlah, subtotal, layanan) untuk kartu kasir.
     *
     * @param  array<int, string>  $orderIds  id order dalam bentuk biner
     * @return array<string, object>
     */
    private function ringkasanFile(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        return File::whereIn('order_id', $orderIds)
            ->selectRaw('order_id, COUNT(*) as jumlah_file, COALESCE(SUM(subtotal), 0) as subtotal, GROUP_CONCAT(DISTINCT layanan) as layanan')
            ->groupBy('order_id')
            ->get()
            ->keyBy(fn ($row) => bin2hex($row->order_id))
            ->all();
    }

    private function baris(object $order, array $ringkasan): array
    {
        $hex = bin2hex($order->id);
        $r = $ringkasan[$hex] ?? null;

        $layanan = $r?->layanan
            ? array_map(fn ($nama) => $this->labelLayanan($nama), explode(',', $r->layanan))
            : [];

        return [
            'id' => $hex,
            'kode' => $this->kodeTampil($order),
            'nama' => $order->nama_pelanggan ?: 'Tanpa nama',
            'no_wa' => $order->no_wa,
            'layanan' => array_values($layanan),
            'jumlah_file' => (int) ($r->jumlah_file ?? 0),
            'subtotal' => (int) ($r->subtotal ?? 0),
            'total' => (int) $order->total_pembayaran,
            'pesan' => $order->tanggal_pesan
                ? Carbon::parse($order->tanggal_pesan)->format('d/m/Y H:i')
                : '-',
            'menunggu' => $order->tanggal_bayar
                ? Carbon::parse($order->tanggal_bayar)->format('d/m/Y H:i')
                : '-',
        ];
    }

    private function kodeTampil(object $order): string
    {
        if (!empty($order->kode)) {
            return $order->kode;
        }

        return strtoupper(substr(bin2hex($order->id), 0, 6));
    }

    private function labelLayanan(?string $layanan): string
    {
        return match ($layanan) {
            'kilat-edit' => 'Kilat edit',
            'kilat-langsung' => 'Kilat langsung',
            'terjadwal' => 'Terjadwal',
            default => ucwords(str_replace('-', ' ', (string) $layanan)),
        };
    }
}
