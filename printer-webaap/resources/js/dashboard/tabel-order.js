const tabel = document.getElementById("tabel-order");
const nav = document.getElementById("paginasi-order");

if (tabel && nav) {
  const tbody = tabel.querySelector("tbody");
  const rupiah = (v) => "Rp " + Number(v).toLocaleString("id-ID");

  const badge = {
    pending: "bg-yellow-100 text-yellow-800",
    processing: "bg-blue-100 text-blue-800",
    success: "bg-green-100 text-green-800",
    fail: "bg-red-100 text-red-800",
  };

  const labelStatus = {
    pending: "Pending",
    processing: "Diproses",
    success: "Berhasil",
    fail: "Gagal",
  };

  const td = (teks, kelas = "") => {
    const el = document.createElement("td");
    el.className = `py-3 pr-4 ${kelas}`;
    el.textContent = teks;
    return el;
  };

  const baris = (o) => {
    const tr = document.createElement("tr");

    const status = document.createElement("td");
    status.className = "py-3 pr-4";
    const span = document.createElement("span");
    span.className = `rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${badge[o.status] ?? "bg-gray-100 text-gray-800"}`;
    span.textContent = labelStatus[o.status] ?? o.status;
    status.append(span);

    tr.append(
      td(o.tanggal),
      td(o.layanan),
      td(o.ukuran),
      td(o.files),
      td(o.halaman),
      status,
      td(rupiah(o.total), "pr-0 text-right font-medium")
    );
    return tr;
  };

  // hasil: [1, 2, "...", 5] dst
  const daftarHalaman = (cur, last) => {
    if (last <= 5) return Array.from({ length: last }, (_, i) => i + 1);
    const set = new Set([1, last, cur - 1, cur, cur + 1]);
    const urut = [...set].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b);
    const hasil = [];
    urut.forEach((p, i) => {
      if (i && p - urut[i - 1] > 1) hasil.push("...");
      hasil.push(p);
    });
    return hasil;
  };

  const tombol = (label, { aktif = false, disabled = false, onClick } = {}) => {
    const b = document.createElement("button");
    b.type = "button";
    b.textContent = label;
    b.disabled = disabled;
    b.className =
      "min-w-9 rounded-lg border px-3 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-40 " +
      (aktif
        ? "border-gray-900 bg-gray-900 text-white"
        : "border-gray-200 bg-white text-gray-900 hover:bg-gray-100");
    if (onClick) b.addEventListener("click", onClick);
    return b;
  };

  const renderNav = (cur, last) => {
    const items = [
      tombol("‹", { disabled: cur <= 1, onClick: () => muat(cur - 1) }),
      ...daftarHalaman(cur, last).map((p) => {
        if (p === "...") {
          const s = document.createElement("span");
          s.className = "px-2 text-gray-500";
          s.textContent = "...";
          return s;
        }
        return tombol(p, { aktif: p === cur, onClick: () => muat(p) });
      }),
      tombol("›", { disabled: cur >= last, onClick: () => muat(cur + 1) }),
    ];
    nav.replaceChildren(...items);
  };

  async function muat(page = 1) {
    try {
      const res = await fetch(`${tabel.dataset.url}?page=${page}`, {
        headers: { Accept: "application/json" },
      });
      const { data, current_page, last_page } = await res.json();

      if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="py-4 text-gray-500">Belum ada order</td></tr>';
      } else {
        tbody.replaceChildren(...data.map(baris));
      }
      renderNav(current_page, last_page);
    } catch {
      tbody.innerHTML = '<tr><td colspan="7" class="py-4 text-red-500">Gagal memuat data</td></tr>';
    }
  }

  muat(1);
}