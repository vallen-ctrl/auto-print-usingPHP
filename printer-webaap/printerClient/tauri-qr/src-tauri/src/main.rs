// tauri-qr — window ringan untuk menampilkan HTML (QR) dari file lokal.
// Pakai:  tauri-qr.exe <path-file.html>
// Window ditutup = proses selesai (RAM langsung bebas).
#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use tauri::{Url, WebviewUrl, WebviewWindowBuilder};

fn main() {
    let path = std::env::args().nth(1).expect("usage: tauri-qr <file.html>");

    tauri::Builder::default()
        .setup(move |app| {
            let url = Url::from_file_path(&path).map_err(|_| "path file tidak valid")?;

            #[allow(unused_mut)]
            let mut builder = WebviewWindowBuilder::new(app, "qr", WebviewUrl::External(url))
                .title("Scan QR - Node Printer")
                .inner_size(420.0, 540.0)
                .resizable(false)
                .center();

            // Hemat RAM di Windows: matikan GPU process WebView2
            #[cfg(windows)]
            {
                builder = builder.additional_browser_args(
                    "--disable-features=msWebOOUI,msPdfOOUI,msSmartScreenProtection --disable-gpu",
                );
            }

            builder.build()?;
            Ok(())
        })
        .run(tauri::generate_context!())
        .expect("gagal menjalankan tauri-qr");
}
