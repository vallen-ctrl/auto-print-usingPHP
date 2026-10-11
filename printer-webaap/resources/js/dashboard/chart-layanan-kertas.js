import ApexCharts from "apexcharts";

const meta = document.getElementById("data-lk");
const input = document.getElementById("bulan-lk"); // sekarang hidden input
const elLayanan = document.getElementById("chart-layanan");
const elKertas = document.getElementById("chart-kertas");

if (meta && input && elLayanan && elKertas) {
  const warna = ["#22c55e", "#3b82f6", "#eab308", "#ef4444", "#a855f7", "#14b8a6"];

  const base = {
    chart: { type: "pie", height: 300, fontFamily: "inherit" },
    colors: warna,
    stroke: { colors: ["#fff"], width: 2 },
    legend: { position: "bottom", fontSize: "12px", markers: { size: 5 } },
    series: [],
    labels: [],
    noData: { text: "Memuat..." },
  };

  const chartLayanan = new ApexCharts(elLayanan, {
    ...base,
    dataLabels: { formatter: (val) => Math.round(val) + "%" },
    tooltip: { y: { formatter: (v) => v + " order" } },
  });

  const chartKertas = new ApexCharts(elKertas, {
    ...base,
    dataLabels: { formatter: (_, o) => o.w.config.series[o.seriesIndex] },
    tooltip: { y: { formatter: (v) => v + " lembar" } },
  });

  chartLayanan.render();
  chartKertas.render();

  const muat = async (bulan) => {
    try {
      const res = await fetch(`${meta.dataset.url}?bulan=${bulan}`, {
        headers: { Accept: "application/json" },
      });
      if (!res.ok) throw new Error("Gagal memuat data");
      const { layanan, kertas } = await res.json();

      chartLayanan.updateOptions({ series: layanan.series, labels: layanan.labels });
      chartKertas.updateOptions({ series: kertas.series, labels: kertas.labels });
    } catch (err) {
      console.error(err);
    }
  };

  // muat pertama kali pakai value hidden input
  muat(input.value);

  // hidden input di-update manual oleh handler .bulan-option,
  // lalu kita dispatch event change di sana → di sini cukup listen
  input.addEventListener("change", (e) => muat(e.target.value));
}