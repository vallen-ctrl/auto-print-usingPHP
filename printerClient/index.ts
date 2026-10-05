// index.ts — entry point
//
//   node-printer.exe              -> pastikan daemon jalan (tray), lalu buka TUI setup
//   node-printer.exe --setup      -> TUI setup saja
//   node-printer.exe --daemon     -> background + system tray
//   node-printer.exe autostart on|off  -> jalan otomatis saat login Windows
//   node-printer.exe uninstall    -> hapus daemon, auto-start, config & file aplikasi
import { isDaemonRunning, startDaemonDetached, runDaemon, setAutostart, IS_COMPILED } from "./daemon";

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

  case "autostart": {
    const on = process.argv[3] !== "off";
    if (on && !IS_COMPILED) {
      console.log("Auto-start hanya untuk versi build (.exe). Jalankan `bun run build` dulu.");
      break;
    }
    const ok = setAutostart(on);
    console.log(ok ? (on ? "✓ Auto-start aktif." : "✓ Auto-start dimatikan.") : "Tidak ada perubahan.");
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