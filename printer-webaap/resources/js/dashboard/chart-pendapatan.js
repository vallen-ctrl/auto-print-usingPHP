import ApexCharts from "apexcharts";

const el = document.getElementById("chart-pendapatan");
const select = document.getElementById("bulan-select");

if (el && select) {
  const tahun = new Date().getFullYear();
  const jumlahHari = (bulan) => new Date(tahun, bulan, 0).getDate();
  const kategori = (bulan) => Array.from({ length: jumlahHari(bulan) }, (_, i) => i + 1);
  const rupiah = (v) => "Rp " + Number(v).toLocaleString("id-ID");

  const ambilData = async (bulan) => {
    const res = await fetch(`${el.dataset.url}?bulan=${bulan}`, {
      headers: { Accept: "application/json" },
    });
    return res.json();
  };

  const bulanAwal = Number(select.value);

  const chart = new ApexCharts(el, {
    chart: { type: "bar", height: 320, fontFamily: "inherit", toolbar: { show: true } },
    series: [{ name: "Pendapatan", data: [] }],
    colors: ["#111827"],
    plotOptions: { bar: { columnWidth: "60%", borderRadius: 4 } },
    dataLabels: { enabled: false },
    grid: { borderColor: "#e5e7eb", strokeDashArray: 4 },
    xaxis: {
      categories: kategori(bulanAwal),
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: { style: { colors: "#6b7280", fontSize: "11px" } },
    },
    yaxis: {
      labels: {
        style: { colors: "#6b7280", fontSize: "12px" },
        formatter: (v) => (v >= 1_000_000 ? v / 1_000_000 + " jt" : v / 1_000 + " rb"),
      },
    },
    tooltip: {
      x: { formatter: (v) => "Tanggal " + v },
      y: { formatter: rupiah },
    },
    noData: { text: "Memuat..." },
  });

  chart.render();
  ambilData(bulanAwal).then((data) => chart.updateSeries([{ name: "Pendapatan", data }]));

  select.addEventListener("change", async (e) => {
    const bulan = Number(e.target.value);
    const data = await ambilData(bulan);
    chart.updateOptions({
      series: [{ name: "Pendapatan", data }],
      xaxis: { categories: kategori(bulan) },
    });
  });
}