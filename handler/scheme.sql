

CREATE TABLE IF NOT EXISTS `user` (
  `sessionid` VARCHAR(255) NOT NULL,
  `name`      VARCHAR(255) NOT NULL,
  `no_wa`     VARCHAR(20)  NOT NULL,
  `create_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sessionid`),
  KEY `idx_user_no_wa` (`no_wa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `layanan` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `nama`           VARCHAR(50) NOT NULL,
  `deskripsi`      TEXT,
  `harga`          BIGINT NOT NULL DEFAULT 0,
  `tanggal_dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `toko` (
  `id`             BINARY(16) NOT NULL,
  `name`           VARCHAR(255) NOT NULL,
  `username`       VARCHAR(50) NOT NULL,
  `password`       VARCHAR(255) NOT NULL, -- simpan HASH (bcrypt/argon2), bukan plaintext
  `tanggal_dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_toko_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `paper` (
  `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`   VARCHAR(8) NOT NULL,
  `width`  INT NOT NULL,
  `height` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_paper_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `computer` (
  `id`             BINARY(16) NOT NULL,
  `name`           VARCHAR(100) NOT NULL,
  `token`          BINARY(16) NOT NULL,
  `tanggal_dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `toko_id`        BINARY(16) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_computer_token` (`token`),
  KEY `idx_computer_toko` (`toko_id`),
  CONSTRAINT `fk_computer_toko` FOREIGN KEY (`toko_id`) REFERENCES `toko`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pengaturan_toko` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `status_buka`       BOOLEAN,
  `biaya_admin`       BIGINT,
  `biaya_layanan`     BIGINT,
  `id_toko`           BINARY(16) NOT NULL,
  `lama_file_dihapus` TINYINT NOT NULL DEFAULT 2,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pengaturan_toko` (`id_toko`),
  CONSTRAINT `fk_pengaturan_toko` FOREIGN KEY (`id_toko`) REFERENCES `toko`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `jam_operasional` (
  `id`            BINARY(16) NOT NULL,
  `nomor_hari`    TINYINT NOT NULL,
  `hari`          VARCHAR(10) NOT NULL,
  `waktu_buka`    TIME,
  `waktu_tutup`   TIME,
  `apakah_dibuka` BOOLEAN NOT NULL,
  `id_pengaturan` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jam_hari` (`id_pengaturan`, `nomor_hari`),
  CONSTRAINT `fk_jam_pengaturan` FOREIGN KEY (`id_pengaturan`) REFERENCES `pengaturan_toko`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `metode_pembayaran` (
  `id`            BINARY(16) NOT NULL,
  `nama`          VARCHAR(255),
  `webhook`       TEXT,
  `token`         TEXT,
  `image_path`    TEXT,
  `id_pengaturan` INT UNSIGNED,
  PRIMARY KEY (`id`),
  KEY `idx_metode_pengaturan` (`id_pengaturan`),
  CONSTRAINT `fk_metode_pengaturan` FOREIGN KEY (`id_pengaturan`) REFERENCES `pengaturan_toko`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `files` (
  `id`             BINARY(16) NOT NULL,
  `name`           TEXT NOT NULL,
  `path`           TEXT NOT NULL,
  `size`           BIGINT NOT NULL,
  `mime` ENUM(
      'application/pdf',
      'application/msword',
      'application/octet-stream',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/zip',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'image/jpeg',
      'image/png'
  ) NOT NULL DEFAULT 'application/octet-stream',
  `diupload_oleh`  VARCHAR(255),
  `tanggal_dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_files_user` (`diupload_oleh`),
  KEY `idx_files_tanggal` (`tanggal_dibuat`), -- buat job auto-delete file lama
  CONSTRAINT `fk_files_user` FOREIGN KEY (`diupload_oleh`) REFERENCES `user`(`sessionid`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
  `id`                   BINARY(16) NOT NULL,
  `tanggal_pesan`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_ambil`        DATE NOT NULL,
  `biaya_admin`          BIGINT NOT NULL DEFAULT 0,
  `total_pembayaran`     BIGINT NOT NULL DEFAULT 0,
  `total_dibayar`        BIGINT NOT NULL DEFAULT 0,
  `pengaturan_id`        INT UNSIGNED NOT NULL,
  `status_pembayaran`    VARCHAR(15) NOT NULL DEFAULT 'belum dibayar',
  `metode_pembayaran`    VARCHAR(255),
  `id_metode_pembayaran` BINARY(16),
  PRIMARY KEY (`id`),
  KEY `idx_orders_toko_tgl` (`pengaturan_id`, `tanggal_pesan`),
  KEY `idx_orders_status` (`status_pembayaran`),
  KEY `idx_orders_tgl_ambil` (`tanggal_ambil`),
  KEY `idx_orders_metode` (`id_metode_pembayaran`),
  CONSTRAINT `fk_orders_pengaturan` FOREIGN KEY (`pengaturan_id`) REFERENCES `pengaturan_toko`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_orders_metode` FOREIGN KEY (`id_metode_pembayaran`) REFERENCES `metode_pembayaran`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `layanan_terpilih` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `files_id`      BINARY(16) NOT NULL,
  `layanan_id`    INT NOT NULL,
  `nama_layanan`  VARCHAR(50) NOT NULL,
  `order_id`      BINARY(16),
  PRIMARY KEY (`id`),
  KEY `idx_lt_files` (`files_id`),
  KEY `idx_lt_layanan` (`layanan_id`),
  KEY `idx_lt_order` (`order_id`),
  CONSTRAINT `fk_lt_files` FOREIGN KEY (`files_id`) REFERENCES `files`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_lt_layanan` FOREIGN KEY (`layanan_id`) REFERENCES `layanan`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_lt_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `configuration` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_id`          BINARY(16) NOT NULL,
  `path`             TEXT NOT NULL,
  `orientasi`        ENUM ('potrait', 'landscape') NOT NULL,
  `warna`            ENUM ('berwarna', 'hitam putih') NOT NULL,
  `ukuran`           VARCHAR(255) NOT NULL,
  `kualitas`         ENUM ('rendah', 'standar', 'tinggi') NOT NULL,
  `skala`            VARCHAR(255) NOT NULL,
  `jumlah_cetak`     SMALLINT NOT NULL,
  `custom_halaman`   TEXT NOT NULL,
  `catatan_tambahan` TEXT NOT NULL,
  `paper_id`         INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_conf_file` (`file_id`),
  KEY `idx_conf_paper` (`paper_id`),
  CONSTRAINT `fk_conf_file` FOREIGN KEY (`file_id`) REFERENCES `files`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_conf_paper` FOREIGN KEY (`paper_id`) REFERENCES `paper`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `file_queue` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tanggal_dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`         ENUM ('pending', 'processing', 'complited', 'failed') NOT NULL DEFAULT 'pending',
  `path`           TEXT NOT NULL,
  `order_id`       BINARY(16) NOT NULL,
  `file_id`        BINARY(16) NOT NULL,
  `computer_id`    BINARY(16) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fq_poll` (`computer_id`, `status`, `id`), -- polling antrian per komputer
  KEY `idx_fq_order` (`order_id`),
  KEY `idx_fq_file` (`file_id`),
  CONSTRAINT `fk_fq_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_fq_file` FOREIGN KEY (`file_id`) REFERENCES `files`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_fq_computer` FOREIGN KEY (`computer_id`) REFERENCES `computer`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;