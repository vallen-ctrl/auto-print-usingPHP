CREATE TABLE IF NOT EXISTS `user` (
	`sessionid` VARCHAR(255) NOT NULL,
	`name` VARCHAR(255) NOT NULL,
	`no_wa` VARCHAR(20) NOT NULL,
	`create_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(`sessionid`)
);


CREATE TABLE IF NOT EXISTS `layanan` (
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`nama` VARCHAR(50) NOT NULL,
	`deskripsi` TEXT,
	`harga` BIGINT NOT NULL DEFAULT 0,
	`tanggal_dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(`id`)
);


CREATE TABLE IF NOT EXISTS `toko` (
	`id` BINARY(16) NOT NULL,
	`name` VARCHAR(255) NOT NULL,
	`username` VARCHAR(50) NOT NULL,
	`password` VARCHAR(255) NOT NULL,
	`tanggal_dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`tanggal_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(`id`),
	CONSTRAINT `uq_toko_username` UNIQUE (`username`)
);


CREATE TABLE IF NOT EXISTS `paper` (
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(8) NOT NULL,
	`width` INTEGER NOT NULL,
	`height` INTEGER NOT NULL,
	PRIMARY KEY(`id`),
	CONSTRAINT `uq_paper_name` UNIQUE (`name`)
);


CREATE TABLE IF NOT EXISTS `computer` (
	`id` BINARY(16) NOT NULL,
	`name` VARCHAR(100) NOT NULL,
	`token` BINARY(16) NOT NULL,
	`tanggal_dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`toko_id` BINARY(16) NOT NULL,
	PRIMARY KEY(`id`),
	CONSTRAINT `uq_computer_token` UNIQUE (`token`)
);


CREATE TABLE IF NOT EXISTS `pengaturan_toko` (
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`status_buka` BOOLEAN,
	`biaya_admin` BIGINT,
	`biaya_layanan` BIGINT,
	`id_toko` BINARY(16) NOT NULL,
	`lama_file_dihapus` TINYINT NOT NULL DEFAULT 2,
	PRIMARY KEY(`id`),
	CONSTRAINT `uq_pengaturan_toko` UNIQUE (`id_toko`)
);


CREATE TABLE IF NOT EXISTS `jam_operasional` (
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`nomor_hari` TINYINT NOT NULL,
	`hari` VARCHAR(10) NOT NULL,
	`waktu_buka` TIME,
	`waktu_tutup` TIME,
	`apakah_dibuka` BOOLEAN NOT NULL,
	`id_pengaturan` INTEGER NOT NULL,
	PRIMARY KEY(`id`),
	CONSTRAINT `uq_jam_hari` UNIQUE (`id_pengaturan`, `nomor_hari`)
);


CREATE TABLE IF NOT EXISTS `metode_pembayaran` (
	`id` BINARY(16) NOT NULL,
	`nama` VARCHAR(255),
	`webhook` TEXT,
	`token` TEXT,
	`image_path` TEXT,
	`id_pengaturan` INTEGER,
	PRIMARY KEY(`id`)
);


CREATE TABLE IF NOT EXISTS `files` (
	`id` BINARY(16) NOT NULL,
	`name` TEXT NOT NULL,
	`path` TEXT NOT NULL,
	`size` BIGINT NOT NULL,
	`mime` ENUM('application/pdf', 'application/msword', 'application/octet-stream', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'image/jpeg', 'image/png') NOT NULL DEFAULT 'application/octet-stream',
	`diupload_oleh` VARCHAR(255),
	`tanggal_dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(`id`)
);


CREATE TABLE IF NOT EXISTS `orders` (
	`id` BINARY(16) NOT NULL,
	`tanggal_pesan` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`tanggal_ambil` DATE NOT NULL,
	`biaya_admin` BIGINT NOT NULL DEFAULT 0,
	`total_pembayaran` BIGINT NOT NULL DEFAULT 0,
	`total_dibayar` BIGINT NOT NULL DEFAULT 0,
	`pengaturan_id` INTEGER NOT NULL,
	`status_pembayaran` VARCHAR(15) NOT NULL DEFAULT 'belum dibayar',
	`metode_pembayaran` VARCHAR(255),
	`id_metode_pembayaran` BINARY(16),
	`session_id` VARCHAR(255) NOT NULL,
	PRIMARY KEY(`id`)
);


CREATE TABLE IF NOT EXISTS `layanan_terpilih` (
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`files_id` BINARY(16) NOT NULL,
	`layanan_id` INTEGER NOT NULL,
	`nama_layanan` VARCHAR(50) NOT NULL,
	`order_id` BINARY(16),
	PRIMARY KEY(`id`)
);


CREATE TABLE IF NOT EXISTS `configuration` (
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`file_id` BINARY(16) NOT NULL,
	`path` TEXT NOT NULL,
	`orientasi` ENUM('potrait', 'landscape') NOT NULL,
	`warna` ENUM('berwarna', 'hitam putih') NOT NULL,
	`ukuran` VARCHAR(255) NOT NULL,
	`kualitas` ENUM('rendah', 'standar', 'tinggi') NOT NULL,
	`skala` VARCHAR(255) NOT NULL,
	`jumlah_cetak` SMALLINT NOT NULL,
	`custom_halaman` TEXT NOT NULL,
	`catatan_tambahan` TEXT NOT NULL,
	`paper_id` INTEGER NOT NULL,
	PRIMARY KEY(`id`)
);


CREATE TABLE IF NOT EXISTS `file_queue` (
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`tanggal_dibuat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`status` ENUM('pending', 'processing', 'complited', 'failed') NOT NULL DEFAULT 'pending',
	`path` TEXT NOT NULL,
	`order_id` BINARY(16) NOT NULL,
	`file_id` BINARY(16) NOT NULL,
	`computer_id` BINARY(16) NOT NULL,
	PRIMARY KEY(`id`)
);


ALTER TABLE `computer`
ADD FOREIGN KEY(`toko_id`) REFERENCES `toko`(`id`)
ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `pengaturan_toko`
ADD FOREIGN KEY(`id_toko`) REFERENCES `toko`(`id`)
ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `jam_operasional`
ADD FOREIGN KEY(`id_pengaturan`) REFERENCES `pengaturan_toko`(`id`)
ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `metode_pembayaran`
ADD FOREIGN KEY(`id_pengaturan`) REFERENCES `pengaturan_toko`(`id`)
ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE `files`
ADD FOREIGN KEY(`diupload_oleh`) REFERENCES `user`(`sessionid`)
ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE `orders`
ADD FOREIGN KEY(`pengaturan_id`) REFERENCES `pengaturan_toko`(`id`)
ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `orders`
ADD FOREIGN KEY(`id_metode_pembayaran`) REFERENCES `metode_pembayaran`(`id`)
ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE `orders`
ADD FOREIGN KEY(`session_id`) REFERENCES `user`(`sessionid`)
ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE `layanan_terpilih`
ADD FOREIGN KEY(`files_id`) REFERENCES `files`(`id`)
ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `layanan_terpilih`
ADD FOREIGN KEY(`layanan_id`) REFERENCES `layanan`(`id`)
ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `layanan_terpilih`
ADD FOREIGN KEY(`order_id`) REFERENCES `orders`(`id`)
ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `configuration`
ADD FOREIGN KEY(`file_id`) REFERENCES `files`(`id`)
ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `configuration`
ADD FOREIGN KEY(`paper_id`) REFERENCES `paper`(`id`)
ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `file_queue`
ADD FOREIGN KEY(`order_id`) REFERENCES `orders`(`id`)
ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `file_queue`
ADD FOREIGN KEY(`file_id`) REFERENCES `files`(`id`)
ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `file_queue`
ADD FOREIGN KEY(`computer_id`) REFERENCES `computer`(`id`)
ON UPDATE CASCADE ON DELETE CASCADE;