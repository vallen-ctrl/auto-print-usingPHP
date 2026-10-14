{{--
    Modal dialog sketsa tangan untuk halaman bergaya coret-coretan (wired-elements).
    Digambar oleh <wired-dialog> yang sudah menyediakan latar gelap dan kotak
    kartu sketsa, jadi isi di dalamnya cukup ditulis di komponen ini.

    Pemakaian dasar:
        <x-modal id="modal-info" type="success" title="Berhasil"
                 message="Cetakan siap diambil besok." />

    Pemakaian dengan isi bebas dan tombol sendiri:
        <x-modal id="modal-konfirmasi" type="warning" title="Yakin?">
            Semua file akan dihapus dari daftar.
            <x-slot:actions>
                <wired-button roughness="1.2" data-modal-close>Tidak</wired-button>
                <wired-button roughness="1.2" elevation="2" id="btn-hapus">Hapus</wired-button>
            </x-slot:actions>
        </x-modal>

    Membuka & menutup (perlu resources/js/shared/modal.js):
        <wired-button roughness="1.2" data-modal-open="modal-info">Lihat info</wired-button>
        <wired-button roughness="1.2" data-modal-close>Tutup</wired-button>
        atau dari JavaScript: bukaModal('modal-info', { message: '...' }).

    Props:
    - id          : id elemen dialog, dipakai sebagai target buka/tutup.
    - type        : info | success | warning | error (warna & ikon).
    - title       : judul modal (kosong berarti tanpa judul).
    - message     : isi pesan singkat (teks biasa). Kalau diisi, slot diabaikan.
    - open        : true kalau modal harus tampil sejak halaman dimuat.
    - dismissible : bisa ditutup lewat latar gelap & tombol Escape.
    - closeLabel  : teks tombol tutup bawaan.
    - icon        : false untuk menyembunyikan ikon tipe.
    - elevation   : tinggi tumpukan kartu sketsa (bawaan wired-dialog: 5).
--}}

@props([
    'id' => 'modal-' . uniqid(),
    'type' => 'info',
    'title' => null,
    'message' => null,
    'open' => false,
    'dismissible' => true,
    'closeLabel' => 'Tutup',
    'icon' => true,
    'elevation' => 5,
])

@php
    // Ikon dibuat untuk semua tipe sekaligus; yang tampil hanya ikon yang cocok
    // dengan data-type, supaya tipe modal bisa diganti lewat JavaScript tanpa
    // perlu mengganti markupnya.
    $ikon = [
        'info' => 'M12 8h.01M11 12h1v4h1',
        'success' => 'M5 11.917 9.724 16.5 19 7.5',
        'warning' => 'M12 9v4m0 4h.01M12 3l9 16H3L12 3z',
        'error' => 'M6 18 17.94 6M18 18 6.06 6',
    ];

    $tipe = array_key_exists($type, $ikon) ? $type : 'info';

    $modalId = $id;
    $judulId = $modalId . '-judul';
    $isiId = $modalId . '-isi';

    $pesan = $message !== null && trim((string) $message) !== '' ? $message : null;
    $adaJudul = $title !== null && trim((string) $title) !== '';
    $aksiSlot = $actions ?? null;
    $adaAksi = $aksiSlot !== null && trim((string) $aksiSlot) !== '';

    $atribut = [
        'id' => $modalId,
        'class' => 'modal',
        'role' => 'dialog',
        'aria-modal' => 'true',
        'aria-describedby' => $isiId,
        'tabindex' => '-1',
        'elevation' => $elevation,
        'roughness' => '1.2',
        'open' => $open,
    ];

    if ($adaJudul) {
        $atribut['aria-labelledby'] = $judulId;
    }

    if ($dismissible) {
        $atribut['data-modal-dismissible'] = 'true';
    }
@endphp

@once
    <style>
        /* ---------- Komponen modal (x-modal) ---------- */

        /* wired-dialog menggambar latar gelap + kotak kartunya sendiri, jadi di
           sini hanya isi kotaknya yang diatur. Token warna & ukuran mengikuti
           halaman sketsa, dengan nilai cadangan supaya tetap wajar di halaman
           lain yang tidak memuat token tersebut. */
        wired-dialog.modal {
            display: block;
            --wired-dialog-z-index: var(--modal-z-index, 200);
        }

        /* Modal tertutup dibuang dari alur dan pohon aksesibilitas. */
        wired-dialog.modal:not([open]) {
            display: none;
        }

        wired-dialog.modal .modal__panel {
            --modal-accent: #2b6cb0;
            display: flex;
            flex-direction: column;
            gap: var(--space-4, 16px);
            width: min(86vw, 26rem);
            max-height: calc(100vh - 3rem);
            max-height: calc(100dvh - 3rem);
            padding: var(--space-2, 8px);
            overflow-y: auto;
            overscroll-behavior: contain;
            color: var(--color-text, #1f1f1f);
            font-family: var(--font-body, "Patrick Hand", "Segoe Print", "Comic Sans MS", cursive);
            font-size: var(--fs-body, 1.0625rem);
            line-height: 1.5;
            text-align: left;
            outline: none; /* fokus dipindah ke sini saat modal dibuka */
        }

        wired-dialog.modal .modal__panel[data-type="success"] {
            --modal-accent: #2f6f4e;
        }

        wired-dialog.modal .modal__panel[data-type="warning"] {
            --modal-accent: #9a7200;
        }

        wired-dialog.modal .modal__panel[data-type="error"] {
            --modal-accent: var(--color-error, #b3261e);
        }

        wired-dialog.modal .modal__head {
            position: sticky;
            top: 0;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: var(--space-3, 12px);
            padding-bottom: var(--space-3, 12px);
            border-bottom: 1px solid var(--color-line, #d8d8d8);
            background: var(--color-surface, #ffffff);
        }

        /* Hanya ikon yang cocok dengan data-type yang ditampilkan. */
        wired-dialog.modal .modal__icon {
            flex: none;
            display: none;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            color: var(--modal-accent);
        }

        wired-dialog.modal .modal__panel[data-type="info"] .modal__icon[data-icon="info"],
        wired-dialog.modal .modal__panel[data-type="success"] .modal__icon[data-icon="success"],
        wired-dialog.modal .modal__panel[data-type="warning"] .modal__icon[data-icon="warning"],
        wired-dialog.modal .modal__panel[data-type="error"] .modal__icon[data-icon="error"] {
            display: inline-flex;
        }

        wired-dialog.modal .modal__icon svg {
            width: 24px;
            height: 24px;
        }

        wired-dialog.modal .modal__title {
            flex: 1 1 auto;
            min-width: 0;
            margin: 0;
            font-family: var(--font-heading, "Caveat", "Patrick Hand", cursive);
            font-size: var(--fs-sub, 1.25rem);
            font-weight: 400;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        wired-dialog.modal wired-icon-button.modal__close {
            flex: none;
            display: inline-flex;
            min-width: 44px;
            min-height: 44px;
            color: var(--color-muted, #57575c);
        }

        wired-dialog.modal wired-icon-button.modal__close svg {
            position: relative;
            width: 20px;
            height: 20px;
        }

        wired-dialog.modal .modal__body {
            overflow-wrap: anywhere;
        }

        wired-dialog.modal .modal__text {
            margin: 0;
        }

        /* Label untuk pembaca layar (tombol ikon hanya berisi gambar). */
        wired-dialog.modal .modal__sr {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: -1px;
            padding: 0;
            border: 0;
            overflow: hidden;
            clip-path: inset(50%);
            white-space: nowrap;
        }

        /* Kepala dan baris tombol tetap menempel di tepi kartu supaya judul,
           tombol tutup, dan tombol aksi selalu bisa dijangkau walau isinya
           panjang dan harus digulir. */
        wired-dialog.modal .modal__actions {
            position: sticky;
            bottom: 0;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: var(--space-3, 12px);
            padding-top: var(--space-4, 16px);
            border-top: 1px solid var(--color-line, #d8d8d8);
            background: var(--color-surface, #ffffff);
        }

        wired-dialog.modal .modal__actions wired-button {
            display: inline-flex;
            min-height: 44px;
            color: var(--color-text, #1f1f1f);
            font-family: inherit;
            font-size: var(--fs-body, 1.0625rem);
        }

        /* Wired elements menyembunyikan diri sampai sketsanya selesai digambar.
           Kalau JavaScript gagal, kontrol tetap terlihat supaya modal tidak
           kosong. */
        wired-dialog.modal wired-button:not(.wired-rendered),
        wired-dialog.modal wired-icon-button:not(.wired-rendered) {
            opacity: 1;
        }

        wired-dialog.modal wired-button:not(.wired-rendered) {
            border: 1px solid var(--color-border, #1f1f1f);
            border-radius: 8px;
        }

        @media (min-width: 768px) {
            wired-dialog.modal .modal__panel {
                width: min(70vw, 28rem);
                gap: var(--space-6, 24px);
                padding: var(--space-3, 12px);
            }

            wired-dialog.modal .modal__title {
                font-size: var(--fs-h3, 1.56rem);
            }

            wired-dialog.modal wired-button:not(.wired-rendered) {
                min-height: 48px;
            }
        }
    </style>
@endonce

<wired-dialog {{ $attributes->merge($atribut) }}>
    <div class="modal__panel" data-type="{{ $tipe }}" tabindex="-1">
        <div class="modal__head">
            @if ($icon)
                @foreach ($ikon as $nama => $path)
                    <span class="modal__icon" data-icon="{{ $nama }}" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $path }}" />
                        </svg>
                    </span>
                @endforeach
            @endif

            <h2 class="modal__title" id="{{ $judulId }}">{{ $title }}</h2>

            @if ($dismissible)
                <wired-icon-button roughness="1.2" class="modal__close" data-modal-close>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 18 17.94 6M18 18 6.06 6" />
                    </svg>
                    <span class="modal__sr">Tutup</span>
                </wired-icon-button>
            @endif
        </div>

        <div class="modal__body" id="{{ $isiId }}">
            @if ($pesan !== null)
                <p class="modal__text">{{ $pesan }}</p>
            @else
                {{ $slot }}
            @endif
        </div>

        @if ($adaAksi || $dismissible)
            <div class="modal__actions">
                @if ($adaAksi)
                    {{ $aksiSlot }}
                @else
                    <wired-button roughness="1.2" elevation="1" data-modal-close>{{ $closeLabel }}</wired-button>
                @endif
            </div>
        @endif
    </div>
</wired-dialog>
