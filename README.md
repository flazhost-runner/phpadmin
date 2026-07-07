# PHPAdmin

Admin panel berbasis PHP 8.3+ native — port dari [NodeAdmin](../NodeAdmin). Tidak menggunakan framework penuh; dibangun dengan Composer, PSR-4, PHP-DI, Eloquent standalone, Phinx, dan nikic/fast-route.

---

## Persyaratan

| Kebutuhan | Versi minimum |
|-----------|---------------|
| PHP | 8.3+ (diuji di 8.5) |
| Composer | 2.x |
| Database | MySQL 8 / PostgreSQL 15 / SQLite 3 |
| Redis | 6+ (untuk session; opsional jika `APP_MODE=api`) |

Extension PHP yang dibutuhkan: `pdo`, `pdo_sqlite` / `pdo_mysql` / `pdo_pgsql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `curl`.

---

## Instalasi

### 1. Clone & install dependensi

```bash
git clone <repo-url> PHPAdmin
cd PHPAdmin
composer install
```

### 2. Konfigurasi environment

```bash
cp .env.example .env
```

Edit `.env` sesuai kebutuhan:

```env
APP_NAME=PHPAdmin
APP_ENV=development
APP_MODE=full          # full = web+API | api = API-only (tanpa session/CSRF/Redis)
APP_URL=http://localhost:8000

# Database — pilih salah satu driver
DB_DRIVER=sqlite       # mysql | pgsql | sqlite
DB_DATABASE=./dev.sqlite3

# Wajib diisi di production
SESSION_SECRET=ganti-dengan-string-acak-minimal-32-karakter
JWT_SECRET=ganti-dengan-string-acak-minimal-32-karakter

# Redis (hanya dibutuhkan jika APP_MODE=full)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

> **SQLite (development cepat):** cukup set `DB_DRIVER=sqlite` dan `DB_DATABASE=./dev.sqlite3`. Redis tidak wajib jika session handler fallback ke file.

### 3. Jalankan migrasi & seeder

```bash
composer migrate            # buat tabel (users, roles, permissions, settings, …)
./vendor/bin/phinx seed:run # isi data awal (admin user + role + setting)
```

### 4. Jalankan server development

```bash
composer start
# server berjalan di http://localhost:8000
```

Server dijalankan dengan OPcache dimatikan (`-d opcache.enable=0`) sehingga perubahan kode langsung terlihat tanpa restart.

### Login default

| Field | Value |
|-------|-------|
| Email | `admin@admin.com` |
| Password | `12345678` |

---

## Perintah yang tersedia

```bash
composer start              # dev server localhost:8000
composer test               # jalankan PHPUnit (27 tests)
composer check              # PHPStan level 6 + PHPCS PSR-12
composer migrate            # Phinx migrate
composer migrate:rollback   # Phinx rollback 1 step
composer migrate:status     # status migrasi
composer make-module        # scaffold modul baru (interaktif)
```

---

## Struktur proyek

```
PHPAdmin/
├── public/
│   └── index.php           # front controller (satu-satunya entry point web)
├── src/
│   ├── Core/               # AppConfig, RouteRegistry, Database, Middleware, …
│   ├── Modules/
│   │   ├── Auth/           # login, register, JWT API auth
│   │   ├── Access/         # User, Role, Permission (RBAC)
│   │   ├── Dashboard/
│   │   ├── Setting/        # tema, FE template switcher
│   │   ├── Profile/
│   │   ├── Media/          # upload gambar
│   │   ├── Components/     # showcase UI
│   │   └── Home/           # landing page publik
│   └── views/              # template PHP (.php)
├── config/
│   ├── definitions.php     # PHP-DI bindings
│   └── modules.php         # daftar modul aktif
├── db/
│   ├── migrations/         # Phinx migrations
│   └── seeds/              # data awal
├── tests/                  # PHPUnit
├── bin/
│   ├── make_module         # generator modul
│   └── add_ui             # generator komponen UI
└── AGENTS.md               # aturan pengembangan (untuk AI & developer)
```

---

## API Reference

Semua endpoint API diawali `/api/v1/`. Header wajib untuk endpoint yang butuh autentikasi:

```
Authorization: Bearer <token>
Content-Type: application/json
```

### Postman Collection

Koleksi Postman lengkap tersedia di [`docs/postman/PHPAdmin.postman_collection.json`](docs/postman/PHPAdmin.postman_collection.json).
Import file tersebut ke Postman, lalu atur variabel `base_url` (default `http://localhost:8001` — port yang dipakai `composer start`) dan `access_token` (JWT dari endpoint login).

### Auth

| Method | Path | Deskripsi |
|--------|------|-----------|
| `POST` | `/api/v1/auth/login` | Login, kembalikan JWT token |
| `POST` | `/api/v1/auth/logout` | Logout, blacklist token |
| `GET` | `/api/v1/auth/me` | Data user yang sedang login |
| `POST` | `/api/v1/auth/register` | Daftar user baru |
| `POST` | `/api/v1/auth/reset/request` | Kirim OTP reset password |
| `POST` | `/api/v1/auth/reset/process` | Proses reset dengan OTP |

**Contoh login:**

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@admin.com","password":"12345678"}'
```

Response:

```json
{
  "success": true,
  "data": {
    "token": "eyJ...",
    "user": { "id": "...", "name": "Administrator", "email": "admin@admin.com" }
  }
}
```

### Access — User

| Method | Path | Deskripsi |
|--------|------|-----------|
| `GET` | `/api/v1/access/user` | List user (query: `q_name`, `q_email`, `q_status`, `q_role`, `q_page`, `q_page_size`) |
| `POST` | `/api/v1/access/user/store` | Buat user baru |
| `GET` | `/api/v1/access/user/{id}/edit` | Detail user |
| `PUT` | `/api/v1/access/user/{id}/update` | Update user |
| `DELETE` | `/api/v1/access/user/{id}/delete` | Hapus user |
| `POST` | `/api/v1/access/user/delete_selected` | Hapus banyak (`{"selected":["id1","id2"]}`) |

### Access — Role

| Method | Path | Deskripsi |
|--------|------|-----------|
| `GET` | `/api/v1/access/role` | List role |
| `POST` | `/api/v1/access/role/store` | Buat role (`{"name","status","desc"}`) |
| `GET` | `/api/v1/access/role/{id}/edit` | Detail role + permissions |
| `PUT` | `/api/v1/access/role/{id}/update` | Update role |
| `DELETE` | `/api/v1/access/role/{id}/delete` | Hapus role |
| `GET` | `/api/v1/access/role/{id}/permission` | List permission untuk role |
| `GET` | `/api/v1/access/role/{id}/permission/{pid}/assign` | Assign 1 permission |
| `GET` | `/api/v1/access/role/{id}/permission/{pid}/unassign` | Unassign 1 permission |
| `POST` | `/api/v1/access/role/{id}/permission/assign_selected` | Assign banyak |
| `POST` | `/api/v1/access/role/{id}/permission/unassign_selected` | Unassign banyak |

### Access — Permission

| Method | Path | Deskripsi |
|--------|------|-----------|
| `GET` | `/api/v1/access/permission` | List permission |
| `POST` | `/api/v1/access/permission/store` | Buat permission |
| `GET` | `/api/v1/access/permission/{id}/edit` | Detail permission |
| `PUT` | `/api/v1/access/permission/{id}/update` | Update permission |
| `DELETE` | `/api/v1/access/permission/{id}/delete` | Hapus permission |
| `POST` | `/api/v1/access/permission/sync` | Sync permission dari RouteRegistry |

### Setting

| Method | Path | Deskripsi |
|--------|------|-----------|
| `GET` | `/api/v1/setting` | Baca setting aktif |
| `PUT` | `/api/v1/setting/update` | Update setting |

---

## Menambah Modul Baru

```bash
composer make-module
# ikuti prompt: nama modul, apakah ada web UI, apakah ada API, dll.
```

Atau baca panduan lengkap di [`AGENTS.md`](AGENTS.md) dan [`docs/`](docs/) jika ada.

---

## Testing

```bash
composer test
```

Output yang diharapkan: **27 tests, 76 assertions** — semua hijau.

Test mencakup:
- Unit: `Helpers`, `RouteRegistry`, `Themes`
- Integration: `UserService` (SQLite in-memory, schema canonical)

---

## Konfigurasi MySQL / PostgreSQL

Ganti `.env`:

```env
# MySQL
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=phpadmin
DB_USERNAME=root
DB_PASSWORD=secret
DB_CHARSET=utf8mb4

# PostgreSQL
DB_DRIVER=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=phpadmin
DB_USERNAME=postgres
DB_PASSWORD=secret
```

Lalu jalankan ulang migrasi:

```bash
composer migrate
./vendor/bin/phinx seed:run
```

---

## Storage & switching backends

Upload gambar (media library editor) memakai adapter storage yang di-switch **hanya lewat `.env`** — tidak perlu ubah kode atau view. Yang disimpan di database adalah **key** objek; **URL render dibangun saat request**, berbeda per driver.

### Driver yang tersedia

| `STORAGE_DRIVER` | Penyimpanan | Key yang disimpan | URL saat render |
|------------------|-------------|-------------------|-----------------|
| `local` | Filesystem lokal (`storage/editor/`) | `editor/<uuid>.<ext>` | `/storage/editor/<uuid>.<ext>` — URL relatif stabil, dilayani langsung |
| `oss` / `s3` | Bucket S3-compatible (AWS S3, Alibaba OSS, MinIO, R2, DigitalOcean Spaces, …) | `media/editor/<uuid>.<ext>` | `/admin/v1/media/file/<name>` → **302 redirect** ke presigned URL (TTL 6 jam); bucket boleh private |

Driver aktif ditentukan otomatis: `STORAGE_DRIVER != local` **dan** `STORAGE_ACCESS_KEY_ID` + `STORAGE_BUCKET` terisi → mode OSS/S3; selain itu → local.

### Cara local dilayani

Front controller mengekspos direktori upload pada prefix URL stabil `/storage/<key>`:

- **Production (nginx/apache, docroot = `public/`):** symlink `public/storage → ../storage` (sudah di-commit) membuat file di `storage/editor/` dapat diakses di `/storage/editor/...`. Web server melayaninya langsung sebagai file statis.
- **Dev server (`composer start`, `php -S`):** `public/index.php` punya passthrough statis yang melayani file di bawah `public/` (termasuk lewat symlink `storage`), lengkap dengan `Content-Type` yang benar. Passthrough ini di-realpath dan dibatasi allow-list root sehingga **path traversal** (`/storage/../../.env`) ditolak.

> URL render dipisah dari path filesystem: prefix web selalu `/storage/...` meski `STORAGE_BASE_PATH` diarahkan ke lokasi lain.

### Switch driver

Ubah `.env` lalu **restart server** (proses membaca config saat boot):

```env
# Local (default) — tanpa kredensial cloud
STORAGE_DRIVER=local
STORAGE_BASE_PATH=storage/uploads

# OSS / S3 — cukup isi kredensial + bucket, tak ada perubahan kode
STORAGE_DRIVER=s3            # atau: oss
STORAGE_ACCESS_KEY_ID=AKIA...
STORAGE_SECRET_ACCESS_KEY=...
STORAGE_ENDPOINT=https://s3.ap-southeast-1.amazonaws.com   # kosongkan utk AWS default
STORAGE_BUCKET=my-bucket
STORAGE_REGION=ap-southeast-1
STORAGE_PATH_STYLE=false     # true utk MinIO / beberapa S3-compat
```

### Migrasi data saat pindah backend

Database menyimpan **key**, bukan URL absolut, jadi memindahkan file antar backend tidak butuh perubahan data — cukup salin objek dengan key yang sama:

```bash
# local → S3
aws s3 sync storage/editor/ s3://my-bucket/media/editor/

# local → OSS (Alibaba)
ossutil cp -r storage/editor/ oss://my-bucket/media/editor/
```

> Perhatikan perbedaan prefix key: local memakai `editor/`, OSS/S3 memakai `media/editor/`. Sesuaikan tujuan sync sesuai tabel di atas.

### Catatan produksi (driver local)

- Isi `storage/editor/` (dan `storage/uploads/`, `storage/di-cache/`, `storage/fe-cache/`) **di-git-ignore**; hanya `.gitkeep` yang di-commit agar direktori tetap ada.
- Filesystem lokal bersifat **ephemeral** di platform seperti container/PaaS — file hilang saat redeploy. Untuk produksi dengan `local`, mount **volume persisten** ke `storage/`, atau gunakan driver `oss`/`s3`.

---

## APP_MODE=api

Untuk deployment sebagai API-only (tanpa session, CSRF, Redis, dan views):

```env
APP_MODE=api
```

Semua endpoint web (`/admin/*`, `/auth/login` via form) tidak aktif. Hanya endpoint `/api/v1/*` yang berjalan.
