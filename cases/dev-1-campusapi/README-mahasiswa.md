# CampusAPI — paket lab DEV-1

Paket ini hanya untuk kelompok DevSecOps-1. Seluruh paket, advisory, token, dan
identitas bersifat sintetis. Tidak ada unduhan dependensi atau publikasi registry.

## Prasyarat

- Windows 10/11, PowerShell 5.1+.
- PHP 8.1+ dengan ekstensi `curl`, `json`, `hash`, `zip`.
  XAMPP PHP (`C:\xampp\php\php.exe`) terdeteksi otomatis.
- Tidak perlu Docker, Python, Composer, atau internet.

## Isi paket

| Path | Fungsi |
| --- | --- |
| `app/` | Source API |
| `composer.json`, `composer.lock` | Dependensi terkunci |
| `Dockerfile` | Konfigurasi image |
| `policy/` | Kebijakan rilis dan advisory sintetis |
| `scan/` | Laporan scanner untuk triase |
| `tools/build.php` | Build artefak deterministik |
| `tools/make-sbom.php` | SBOM CycloneDX terikat artefak |
| `tools/gate.ps1` | Gate rilis |
| `tools/functional.php` | Uji fungsi API |
| `student-card.md` | Kasus dan Q1–Q12 |

## Menjalankan

```powershell
# Baseline gate (sengaja gagal)
powershell -ExecutionPolicy Bypass -File .\tools\gate.ps1 -RepoPath .

# Lihat keputusan dan reason code
Get-Content .\build\gate-result.json -Raw

# Setelah perbaikan: build, SBOM, gate
php tools\build.php .
php tools\make-sbom.php .
powershell -ExecutionPolicy Bypass -File .\tools\gate.ps1 -RepoPath .

# Bekukan gate + kebijakan sebelum holdout
powershell -ExecutionPolicy Bypass -File .\tools\gate.ps1 -RepoPath . -Freeze
```

Gate memakai port lokal `8096` untuk uji fungsi. Jika port dipakai, tambahkan
`-Port 8100`.

## Reason code yang mungkin muncul

| Kode | Arti |
| --- | --- |
| `DEP_<id>` | Dependensi terkena advisory pada severity pemblokir |
| `SECRET_IN_SOURCE` | String berbentuk kredensial ada di source |
| `CONTAINER_ROOT_USER` | Container berjalan sebagai root atau tanpa USER |
| `SBOM_MISSING` | SBOM belum dibuat |
| `SBOM_SUBJECT_MISMATCH` | SBOM tidak terikat ke artefak yang diuji |
| `SBOM_PACKAGE_MISMATCH` | Paket SBOM tidak sama dengan lockfile |
| `FUNCTIONAL_TEST_FAILED` | Uji fungsi gagal pada artefak |
| `ARTIFACT_*` | Artefak tidak cocok dengan source/lockfile/digest |
| `POLICY_WEAKENED` | Kebijakan mematikan pemblokir atau mengizinkan suppression menyeluruh |

## Troubleshooting

| Gejala | Tindakan |
| --- | --- |
| `PHP 8.1+ not found` | Pasang XAMPP/PHP atau tambahkan ke `PATH`. |
| Port 8096 dipakai | Tambahkan `-Port 8100`. |
| `running scripts is disabled` | Gunakan `powershell -ExecutionPolicy Bypass -File ...`. |
| Gate lulus padahal ada temuan | Periksa apakah policy melemah atau SBOM kedaluwarsa. |
| SBOM mismatch | Jalankan ulang `php tools\make-sbom.php .` setelah build terakhir. |
