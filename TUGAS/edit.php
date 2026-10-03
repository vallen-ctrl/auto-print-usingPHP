<?php
require_once 'database.php';  // Untuk koneksi database
db::start(); 

// 1. Pastikan ada ID tugas yang dikirim lewat URL
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$id = $_GET['id'];

// 2. PROSES UPDATE DATA (Berjalan ketika tombol "Update Tugas" ditekan)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $data_update = [
        'judul'     => $_POST['judul'],
        'deskripsi' => $_POST['deskripsi'],
        'category'  => $_POST['category_id'],
        'priority'  => $_POST['priority'],
        'status'    => $_POST['status']
    ];

    if (!empty($_POST['tenggat'])) {
        $data_update['tenggat'] = $_POST['tenggat'];
    } else {
        $data_update['tenggat'] = null; // Kosongkan tanggal jika dihapus user
    }

    // Fungsi update berdasarkan ID
    db::update('ToDo', $data_update, 'id', $id);
    
    // Lempar kembali ke halaman utama
    header("Location: index.php?status=sukses");
    exit();
}

// 3. TARIK DATA TUGAS SPESIFIK UNTUK MENGISI FORM
$result_tugas = db::get("SELECT * FROM ToDo WHERE id = ?", [$id]);
$tugas = mysqli_fetch_assoc($result_tugas);

// Jika ID tidak ditemukan di database, kembalikan ke index
if (!$tugas) {
    header("Location: index.php");
    exit();
}

// 4. TARIK DATA KATEGORI UNTUK DROPDOWN
$result_kategori = db::get("SELECT * FROM Category");
$kategori_list = [];
if ($result_kategori) {
    while ($row = mysqli_fetch_assoc($result_kategori)) {
        $kategori_list[] = $row;
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Tugas - MyAgenda</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-2xl bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
        
        <div class="flex items-center gap-4 mb-8">
            <a href="index.php" class="p-2 bg-slate-50 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Edit Agenda</h2>
                <p class="text-sm text-slate-500 mt-1">Perbarui informasi tugasmu di bawah ini.</p>
            </div>
        </div>
        
        <form action="" method="POST" class="space-y-5">
            <!-- Inject ID Tugas secara sembunyi -->
            <input type="hidden" name="id" value="<?= $tugas['id'] ?>">
            
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-2">Judul Tugas</label>
                <!-- Menampilkan judul yang ditarik dari DB -->
                <input type="text" name="judul" required value="<?= htmlspecialchars($tugas['judul']) ?>" 
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-400 outline-none transition-all duration-200">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-2">Deskripsi</label>
                <!-- Menampilkan deskripsi dari DB di dalam tag textarea -->
                <textarea name="deskripsi" rows="4" 
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-400 outline-none transition-all duration-200"><?= htmlspecialchars($tugas['deskripsi'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Kategori</label>
                    <select name="category_id" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-400 outline-none">
                        <?php foreach($kategori_list as $kat): ?>
                            <!-- Mengecek kategori mana yang cocok dengan data tugas saat ini untuk diberi atribut 'selected' -->
                            <option value="<?= $kat['id'] ?>" <?= ($tugas['category'] == $kat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['judul']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Prioritas</label>
                    <select name="priority" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-400 outline-none">
                        <option value="Low" <?= ($tugas['priority'] == 'Low') ? 'selected' : '' ?>>Low</option>
                        <option value="Medium" <?= ($tugas['priority'] == 'Medium') ? 'selected' : '' ?>>Medium</option>
                        <option value="High" <?= ($tugas['priority'] == 'High') ? 'selected' : '' ?>>High</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Tenggat</label>
                    <!-- Mengonversi format tanggal dari DB ke format input date (YYYY-MM-DD) -->
                    <input type="date" name="tenggat" 
                        value="<?= $tugas['tenggat'] ? date('Y-m-d', strtotime($tugas['tenggat'])) : '' ?>"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-400 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-400 outline-none">
                        <option value="To Do" <?= ($tugas['status'] == 'To Do') ? 'selected' : '' ?>>To Do</option>
                        <option value="In Progress" <?= ($tugas['status'] == 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                        <option value="Done" <?= ($tugas['status'] == 'Done') ? 'selected' : '' ?>>Done</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 flex gap-3">
                <a href="index.php" class="w-1/3 flex items-center justify-center px-4 py-3 rounded-xl text-slate-600 font-semibold bg-slate-100 hover:bg-slate-200 transition-colors">
                    Batal
                </a>
                <button type="submit" class="w-2/3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition-colors duration-200 shadow-md shadow-indigo-200">
                    Update Tugas
                </button>
            </div>
        </form>
    </div>
</body>
</html>