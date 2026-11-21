// index.ts — entry point
//
//   node-printer (.exe)        -> pastikan daemon jalan (tray), lalu buka TUI setup
//   node-printer --setup       -> TUI setup saja
//   node-printer --daemon      -> background (system tray hanya di Windows)
//   node-printer qr            -> ambil QR dari server & tampilkan di window Tauri
//   node-printer autostart on|off  -> jalan otomatis saat login (Windows/Linux)
//   node-printer stop          -> hentikan daemon (tanpa menghapus config)
//   node-printer status        -> cek daemon sedang berjalan atau tidak
//   node-printer uninstall     -> hapus daemon, auto-start, config & file aplikasi
import {
  isDaemonRunning,
  startDaemonDetached,
  runDaemon,
  setAutostart,
  stopDaemon,
  daemonStatus,
  IS_COMPILED,
} from "./daemon";

const arg = process.argv[2];

switch (arg) {
  case "--daemon":
    await runDaemon();
    break;

  case "--setup": {
    const { main } = await import("./setup");
    await main();
    break;
  }

  case "qr": {
    const { prepareQr, showQr } = await import("./qr");
    try {
      await showQr(await prepareQr());
    } catch (e) {
      console.error("✗ " + (e instanceof Error ? e.message : String(e)));
      process.exit(1);
    }
    break;
  }

  case "autostart": {
    const on = process.argv[3] !== "off";
    if (on && !IS_COMPILED) {
      console.log("Auto-start hanya untuk versi build. Jalankan `bun run build:win` atau `bun run build:linux` dulu.");
      break;
    }
    const ok = setAutostart(on);
    console.log(ok ? (on ? "✓ Auto-start aktif." : "✓ Auto-start dimatikan.") : "Tidak ada perubahan.");
    break;
  }

  case "stop": {
    if (!(await isDaemonRunning())) {
      console.log("Daemon tidak sedang berjalan.");
      break;
    }

    const ok = await stopDaemon();
    console.log(ok ? "✓ Daemon dihentikan." : "✗ Gagal menghentikan daemon.");
    process.exit(ok ? 0 : 1);
  }

  case "status": {
    const st = await daemonStatus();

    console.log(
      st.running
        ? `● Daemon berjalan${st.pid ? ` (PID ${st.pid})` : ""}`
        : "○ Daemon tidak berjalan"
    );

    if (st.running && st.state) {
      console.log(`  SSE: ${st.state}${st.info ? ` (${st.info})` : ""}`);
    }
    break;
  }

  case "uninstall": {
    const { uninstallFlow } = await import("./setup");
    await uninstallFlow();
    break;
  }

  default: {
    if (!(await isDaemonRunning())) {
      startDaemonDetached();
      await new Promise((r) => setTimeout(r, 800));
    }
    const { main } = await import("./setup");
    await main();
  }
}