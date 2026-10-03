<?php
require_once 'database.php';   // Untuk koneksi database
db::start(); 

// Suntikkan kategori bawaan ke database jika belum ada
$kategori_bawaan = [
    '🎓 Kuliah' => '#8b5cf6', // Ungu
    '💻 Project' => '#3b82f6', // Biru
    '🏠 Personal' => '#10b981' // Hijau
];

foreach ($kategori_bawaan as $judul => $warna) {
    $cek = db::get("SELECT id FROM Category WHERE judul = '$judul'");
    if (mysqli_num_rows($cek) == 0) {
        db::insert('Category', ['judul' => $judul, 'color' => $warna]);
    }
}


// 1. LOGIKA SIMPAN TUGAS (Method POST)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $kategori_id = $_POST['category_id'];
    
    if (strpos($kategori_id, 'new_') === 0) {
        $kategori_id = db::insert('Category', [
            'judul' => $_POST['new_category_name'],
            'color' => $_POST['new_category_color']
        ]);
    }

    $data_tugas = [
        'judul'     => $_POST['judul'],
        'deskripsi' => $_POST['deskripsi'],
        'category'  => $kategori_id, 
        'priority'  => $_POST['priority'],
        'status'    => $_POST['status']
    ];

    if (!empty($_POST['tenggat'])) {
        $data_tugas['tenggat'] = $_POST['tenggat'];
    }

    db::insert('ToDo', $data_tugas);
    header("Location: index.php?status=sukses");
    exit();
}

// 2. LOGIKA TARIK DATA TUGAS & KATEGORI (Method GET)

$sql_tugas = "SELECT ToDo.*, Category.judul AS nama_kategori, Category.color AS warna_kategori 
              FROM ToDo 
              LEFT JOIN Category ON ToDo.category = Category.id 
              ORDER BY ToDo.create_at DESC";
$result_tugas = db::get($sql_tugas);
$daftar_tugas = [];
if ($result_tugas) {
    while ($row = mysqli_fetch_assoc($result_tugas)) {
        $daftar_tugas[] = $row;
    }
}

$result_kategori = db::get("SELECT * FROM Category WHERE judul IN ('🎓 Kuliah', '💻 Project', '🏠 Personal')"); 
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
    <title>Agenda & To-Do List</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .modal-enter { opacity: 0; transform: scale(0.95); }
        .modal-enter-active { opacity: 1; transform: scale(1); transition: all 0.2s ease-out; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen relative">

    <nav class="bg-white/70 backdrop-blur-md border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-indigo-900 tracking-tight">📝 MyAgenda</h1>
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-slate-500"><?= date('l, d M Y') ?></span>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        
        <?php if (isset($_GET['status']) && $_GET['status'] === 'sukses'): ?>
        <div id="alertSuccess" class="mb-6 flex items-center justify-between bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-2xl shadow-sm transition-all duration-300">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="font-semibold">Berhasil! Tugas barumu sudah tersimpan ke dalam agenda.</span>
            </div>
            <button onclick="document.getElementById('alertSuccess').style.display='none'" class="text-emerald-500 hover:text-emerald-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <?php elseif (isset($_GET['pesan']) && $_GET['pesan'] === 'hapus_sukses'): ?>
        <div id="alertHapus" class="mb-6 flex items-center justify-between bg-rose-50 border border-rose-200 text-rose-700 px-5 py-4 rounded-2xl shadow-sm transition-all duration-300">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <span class="font-semibold">Sip! Tugas berhasil dihapus dari agendamu.</span>
            </div>
            <button onclick="document.getElementById('alertHapus').style.display='none'" class="text-rose-500 hover:text-rose-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <h2 class="text-lg font-bold text-slate-800 mb-5">Tambah Tugas Baru</h2>
                    <form action="" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Judul Tugas</label>
                            <input type="text" name="judul" required placeholder="Mau ngerjain apa hari ini?" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Deskripsi</label>
                            <textarea name="deskripsi" rows="3" placeholder="Detail tugas..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-400 outline-none"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-1">Kategori</label>
                                <select name="category_id" id="categorySelect" onchange="handleCategoryChange(this)" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-400 outline-none cursor-pointer">
                                    <?php foreach($kategori_list as $kat): ?>
                                        <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['judul']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="add_new" class="font-bold text-indigo-600">+ Tambah Baru...</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-1">Prioritas</label>
                                <select name="priority" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-400 outline-none">
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-1">Tenggat</label>
                                <input type="date" name="tenggat" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-400 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-1">Status</label>
                                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-400 outline-none">
                                    <option value="To Do">To Do</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Done">Done</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="w-full mt-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl shadow-md shadow-indigo-200 transition-colors">
                            Simpan Tugas
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-8">
                <div class="flex flex-wrap items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-slate-800">Daftar Agenda</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    
                    <?php if (count($daftar_tugas) === 0): ?>
                        <div class="col-span-2 text-center py-10 text-slate-500">Belum ada tugas yang ditambahkan.</div>
                    <?php endif; ?>

                    <?php foreach ($daftar_tugas as $tugas): ?>
                        <?php 
                            $prio_class = 'bg-slate-100 text-slate-700';
                            if ($tugas['priority'] == 'High') $prio_class = 'bg-rose-50 text-rose-700';
                            if ($tugas['priority'] == 'Medium') $prio_class = 'bg-amber-50 text-amber-700';
                            if ($tugas['priority'] == 'Low') $prio_class = 'bg-emerald-50 text-emerald-700';

                            $status_class = 'bg-slate-100 text-slate-600';
                            if ($tugas['status'] == 'In Progress') $status_class = 'bg-blue-100 text-blue-700';
                            if ($tugas['status'] == 'Done') $status_class = 'bg-emerald-100 text-emerald-700';
                            
                            $hex_warna = $tugas['warna_kategori'] ? $tugas['warna_kategori'] : '#4f46e5';
                        ?>

                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow duration-200 flex flex-col justify-between group">
                            <div>
                                <div class="flex justify-between items-start mb-3">
                                    <span class="px-3 py-1 rounded-lg text-xs font-bold tracking-wide" style="background-color: <?= $hex_warna ?>20; color: <?= $hex_warna ?>;">
                                        <?= htmlspecialchars($tugas['nama_kategori'] ?? 'Tanpa Kategori') ?>
                                    </span>
                                    <span class="px-3 py-1 rounded-lg text-xs font-bold <?= $status_class ?>">
                                        <?= htmlspecialchars($tugas['status']) ?>
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 mb-2 group-hover:text-indigo-600 transition-colors"><?= htmlspecialchars($tugas['judul']) ?></h3>
                                <p class="text-slate-500 text-sm leading-relaxed mb-4"><?= htmlspecialchars($tugas['deskripsi']) ?></p>
                            </div>
                            
                            <div class="flex justify-between items-center pt-4 border-t border-slate-100">
                                <div class="flex items-center gap-2">
                                    <?php if ($tugas['tenggat']): ?>
                                    <div class="flex items-center gap-1.5 text-sm font-semibold px-2 py-1.5 rounded-lg <?= $prio_class ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span><?= date('d M Y', strtotime($tugas['tenggat'])) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex items-center gap-1">
                                    <a href="edit.php?id=<?= $tugas['id'] ?>" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all duration-200">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    <a href="hapus.php?id=<?= $tugas['id'] ?>" onclick="return confirm('Yakin ingin menghapus tugas ini?')" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all duration-200">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pop-up Kategori -->
    <div id="categoryModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl transform transition-all modal-enter" id="modalContent">
            <h3 class="text-lg font-bold text-slate-800 mb-1">Tambah Kategori Baru</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Nama Kategori</label>
                    <input type="text" id="newCategoryName" placeholder="Contoh: 🎨 Desain UI/UX" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Warna Label</label>
                    <div class="flex items-center gap-3">
                        <input type="color" id="newCategoryColor" value="#4f46e5" class="h-10 w-10 rounded-lg cursor-pointer border-0 p-0">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-xl text-slate-600 font-semibold hover:bg-slate-100 transition-colors">Batal</button>
                    <button type="button" onclick="saveCategory()" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition-colors">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const categorySelect = document.getElementById('categorySelect');
        const categoryModal = document.getElementById('categoryModal');
        const modalContent = document.getElementById('modalContent');
        const newCategoryName = document.getElementById('newCategoryName');
        const newCategoryColor = document.getElementById('newCategoryColor');
        
        let previousCategoryValue = categorySelect.value;

        function handleCategoryChange(select) {   
            if (select.value === 'add_new') openModal();
            else previousCategoryValue = select.value;
        }

        function openModal() {
            categoryModal.classList.remove('hidden');
            setTimeout(() => modalContent.classList.add('modal-enter-active'), 10);
            newCategoryName.focus();
        }

        function closeModal() {
            modalContent.classList.remove('modal-enter-active');
            setTimeout(() => {
                categoryModal.classList.add('hidden');
                categorySelect.value = previousCategoryValue;
                newCategoryName.value = '';
                newCategoryColor.value = '#4f46e5';
            }, 200);
        }

        // Simpan kategori baru

        function saveCategory() {
            const name = newCategoryName.value.trim();
            const color = newCategoryColor.value; 
            
            if (name) {
                const newId = 'new_' + Date.now(); 
                const newOption = document.createElement('option');
                newOption.value = newId; 
                newOption.text = name;
                
                const options = categorySelect.options;
                categorySelect.add(newOption, options[options.length - 1]);
                
                categorySelect.value = newOption.value;
                previousCategoryValue = newOption.value;

                const form = document.querySelector('form');
                const hiddenName = document.createElement('input');
                hiddenName.type = 'hidden';
                hiddenName.name = 'new_category_name';
                hiddenName.value = name;
                form.appendChild(hiddenName);

                const hiddenColor = document.createElement('input');
                hiddenColor.type = 'hidden';
                hiddenColor.name = 'new_category_color';
                hiddenColor.value = color;
                form.appendChild(hiddenColor);
                
                closeModal();
            }
        }
    </script>
</body>
</html>