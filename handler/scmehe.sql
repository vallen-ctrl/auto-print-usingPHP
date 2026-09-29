CREATE DATABASE IF NOT EXISTS marketplace;

CREATE TABEL IF NOT EXISTS user (
    sessionid varchar(255) NOT NULL PRIMARY KEY,
    name varchar(255) NOT NULL,
    no_wa varchar(14) NOT NULL,
    create_at 
);

CREATE TABLE IF NOT EXISTS layanan(
    id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nama varchar(50) NOT NULL, 
    deskripsi text,
    harga BIGINT NOT NULL DEFAULT 0,
    tanggal_dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABEL IF NOT EXISTS files(
    id BINARY(16) NOT NULL PRIMARY KEY,
    name text,
    path text,
    size BIGINT UNSIGNED NOT NULL ,
    mime text ENUM(
        'application/pdf',
        'application/msword',
        'application/octet-stream',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ) DEFAULT NOT NULL,
    diupload_oleh varchar(255) NOT NULL,
    tanggal_dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (diupload_oleh) REFERENCES user(id)
);


CREATE TABLE IF NOT EXISTS order (
    id varchar(8) NOT NULL PRIMARY KEY,
    tanggal_pesan TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tanggal_ambil DATE NOT NULL,
    biaya_admin BIGINT NOT NULL DEFAULT 0,
    total_pembayaran BIGINT NOT NULL DEFAULT 0,
    total_dibayar BIGINT NOT NULL DEFAULT 0,

)