-- Migrasi: tambah akun pembeli (login pelanggan, terpisah dari admin)
-- Jalankan SEKALI di database PostgreSQL yang dipakai Railway.

CREATE TABLE IF NOT EXISTS pembeli (
    id_pembeli    SERIAL PRIMARY KEY,
    nama_pembeli  VARCHAR(150) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    telepon       VARCHAR(20)  NOT NULL,
    alamat        TEXT         NOT NULL,
    dibuat_pada   TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- Menandai pesanan mana yang dibuat oleh pembeli yang login (boleh NULL
-- untuk pembeli yang checkout tanpa login, seperti sebelumnya).
ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS id_pembeli INTEGER
    REFERENCES pembeli(id_pembeli) ON DELETE SET NULL;
