const tabel = document.getElementById("tabel-tipe-file");

if (tabel) {
  const tbody = tabel.querySelector("tbody");

  const buatBaris = (i, { tipe, jumlah }) => {
    const tr = document.createElement("tr");

    const tdTipe = document.createElement("td");
    tdTipe.className = "py-2.5 text-gray-900";
    tdTipe.textContent = `${i + 1}. ${tipe}`;

    const tdJumlah = document.createElement("td");
    tdJumlah.className = "py-2.5 text-right font-medium text-gray-900";
    tdJumlah.textContent = jumlah;

    tr.append(tdTipe, tdJumlah);
    return tr;
  };

  fetch(tabel.dataset.url, { headers: { Accept: "application/json" } })
    .then((res) => res.json())
    .then((data) => {
      tbody.replaceChildren(...data.map((row, i) => buatBaris(i, row)));
    })
    .catch(() => {
      tbody.innerHTML =
        '<tr><td colspan="2" class="py-2.5 text-red-500">Gagal memuat data</td></tr>';
    });
}