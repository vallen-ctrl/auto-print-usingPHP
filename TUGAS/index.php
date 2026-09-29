<?php
    require_once 'database.php';

    db::start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Cetak PDF — Wireframe</title>
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600;700&display=swap" rel="stylesheet">
<script type="module" src="https://cdnjs.cloudflare.com/ajax/libs/wired-elements/2.1.2/wired-elements-esm.min.js"></script>
<style>
  :root{ --bg:#f7f6f3; --ink:#20242c; padding-top:env(safe-area-inset-top,0px); padding-bottom:env(safe-area-inset-bottom,0px); }
  html,body{ margin:0; background:var(--bg); }
  body{
    font-family:"Caveat",cursive; color:var(--ink);
    display:flex; justify-content:center; padding:28px 16px; box-sizing:border-box;
  }
  #page{ width:100%; max-width:340px; position:relative; }

  .navbar{ display:flex; gap:10px; margin-bottom:26px; }
  .navbar wired-card{ padding:6px 12px; font-size:1rem; }

  wired-card.main{ display:block; padding:22px 18px 24px; }

  .badge{
    display:inline-block; border:1.5px solid var(--ink); border-radius:14px;
    padding:3px 10px; font-size:0.9rem; margin-bottom:14px; transform:rotate(-0.6deg);
  }

  h1{ font-size:1.9rem; line-height:1.15; margin:0 0 10px; font-weight:700; }
  .sub{ font-size:1rem; line-height:1.3; margin:0 0 18px; max-width:95%; }

  .dropzone{
    border:2px dashed var(--ink); border-radius:16px; padding:26px 12px;
    text-align:center; margin-bottom:16px; transform:rotate(0.3deg);
  }
  .dropzone .icon{
    width:46px; height:46px; margin:0 auto 10px; border:2px solid var(--ink);
    border-radius:10px; display:flex; align-items:center; justify-content:center;
    font-size:1.4rem; transform:rotate(-2deg);
  }
  .dropzone .main-txt{ font-size:1.15rem; font-weight:600; }
  .dropzone .caption{ font-size:0.85rem; opacity:0.75; margin-top:4px; }

  wired-button.cta{ width:100%; font-size:1.05rem; }
  wired-button.cta::part(path){ }

  .actions{ display:flex; gap:12px; margin-top:28px; }
  .actions wired-button{ flex:1; font-size:1.05rem; position:relative; }

  #overlay{ position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; }
</style>
</head>
<body>
<div id="page">

  <div class="navbar">
    <wired-card>logo jasa</wired-card>
    <wired-card>nama print</wired-card>
  </div>

  <wired-card class="main" elevation="1">
    <span class="badge">&#9889; CETAK KILAT 1 JAM JADI</span>
    <h1>Tinggal Upload PDF,<br>Langsung Jadi</h1>
    <p class="sub">Cetak dokumen PDF tanpa ribet. Cukup upload file, kami yang cetakkan langsung.</p>

    <div class="dropzone">
      <div class="icon">&#8593;</div>
      <div class="main-txt">upload file PDF ke sini</div>
      <div class="caption">format yang didukung: PDF (maks. 20MB)</div>
    </div>

    <wired-button class="cta">Pilih File PDF Sekarang</wired-button>
  </wired-card>

  <div class="actions">
    <wired-button id="btnKilat">Cetak Kilat</wired-button>
    <wired-button id="btnJadwal">Cetak Terjadwal</wired-button>
  </div>

  <svg id="overlay"></svg>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/rough.js/2.2.4/rough.min.js"></script>
<script>
  window.addEventListener('load', () => {
    const page = document.getElementById('page');
    const svg = document.getElementById('overlay');
    const rect = page.getBoundingClientRect();
    svg.setAttribute('viewBox', `0 0 ${rect.width} ${rect.height}`);

    const rc = rough.svg(svg, { options: { stroke: '#20242c', strokeWidth: 1.6, roughness: 1.4 } });

    const card = page.querySelector('wired-card.main').getBoundingClientRect();
    const btn = document.getElementById('btnJadwal').getBoundingClientRect();

    const offX = rect.left, offY = rect.top;
    // diagonal connector from bottom-right of main card to top of "Cetak Terjadwal"
    svg.appendChild(rc.line(
      card.right - offX - 10, card.bottom - offY - 6,
      btn.left - offX + btn.width / 2, btn.top - offY
    ));
    // squiggly underline beneath "Cetak Terjadwal"
    const uy = btn.bottom - offY + 4;
    svg.appendChild(rc.line(btn.left - offX + 8, uy, btn.right - offX - 8, uy, { roughness: 2.2 }));
  });
</script>
</body>
</html>