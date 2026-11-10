# Kartu kasus DEV-1 — CampusAPI release gate

**Untuk:** kelompok DevSecOps-1 saja. **Kalender:** 20 November–18 Desember 2026.
**Kasus:** rilis API kecil dengan temuan dependensi, konfigurasi image, dan string
rahasia dummy.

## Situasi

Tim pengembang KampusNusa akan merilis **CampusAPI**, API kecil untuk katalog
kursus dan pendaftaran mahasiswa. Pipeline awal gagal. Sebagian alarm berasal
dari temuan nyata, sebagian dari laporan scanner yang sudah kedaluwarsa atau
tidak berlaku. Manajemen meminta keputusan rilis yang dapat dipertanggungjawabkan,
bukan sekadar badge hijau.

## Tujuan

1. Memetakan source, dependensi, jalur build, dan artefak akhir.
2. Mereproduksi baseline gate dan mencatat alasan gagal.
3. Menilai temuan: memblokir, dapat diterima sementara, atau false positive.
4. Melakukan perbaikan minimum tanpa merusak fungsi API.
5. Menghasilkan SBOM yang terikat pada artefak yang benar-benar diuji.
6. Menjalankan gate pada fixture holdout dan menjelaskan keputusan.

## Artefak yang tersedia

| Path | Isi |
| --- | --- |
| `app/` | Source API (health, kursus, mahasiswa, pendaftaran) |
| `composer.json`, `composer.lock` | Dependensi dan versi terkunci (sintetis) |
| `Dockerfile` | Konfigurasi image |
| `policy/release-policy.json` | Kebijakan rilis |
| `policy/advisories.json` | Basis advisory sintetis |
| `scan/` | Laporan SCA, SAST, secret, container (bukti untuk triase) |
| `tools/build.php` | Build artefak deterministik |
| `tools/make-sbom.php` | SBOM CycloneDX terikat digest artefak |
| `tools/gate.ps1` | Gate rilis |
| `tools/functional.php` | Uji fungsi API |

## Menjalankan baseline

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\gate.ps1 -RepoPath .
# hasil: build\gate-result.json (keputusan fail + reason code)
```

Baseline memang gagal. Tugas Anda adalah menjelaskan mengapa, lalu memperbaiki.

## Alur perbaikan yang disarankan

```powershell
# 1. Setelah mengubah source/dependensi/Dockerfile:
php tools\build.php .
# 2. Hasilkan SBOM untuk artefak yang baru dibangun:
php tools\make-sbom.php .
# 3. Jalankan gate:
powershell -ExecutionPolicy Bypass -File .\tools\gate.ps1 -RepoPath .
# 4. Bekukan gate dan kebijakan sebelum holdout:
powershell -ExecutionPolicy Bypass -File .\tools\gate.ps1 -RepoPath . -Freeze
```

Gate menulis `build/gate-result.json` dengan `source_revision`, `lockfile_sha256`,
`artifact_digest`, `sbom_subject_digest`, `policy_version`, tahapan, temuan, dan
reason code.

## Aturan gate

- Severity yang memblokir ditentukan `policy/release-policy.json`.
- Temuan dependensi dihitung ulang dari `composer.lock` dan `policy/advisories.json`.
  Laporan `scan/sca.json` adalah bukti, bukan kebenaran.
- String rahasia diperiksa ulang dari source; laporan scanner tidak dipercaya mentah.
- Container harus berjalan sebagai non-root.
- SBOM wajib ada dan `metadata.component.hashes[0].content` harus sama dengan
  `artifact_digest`.
- Uji fungsi harus lulus terhadap artefak yang dibangun, bukan source lain.
- Menonaktifkan severity pemblokir atau mengizinkan suppression menyeluruh
  menghasilkan `POLICY_WEAKENED`.

## Q1–Q12

**Q1.** Apa aplikasi/paket, dependensi, jalur build, artefak akhir, dan pihak yang
berwenang menyetujui rilis?

**Q2.** Tiga risiko supply chain terpenting apa yang dapat diuji dari fixture?
Pisahkan fakta dan asumsi.

**Q3.** Pada fixture awal, gate mana lulus dan gagal? Catat versi tool, input,
output, serta satu false positive potensial.

**Q4.** Bagaimana Anda mencegah kebocoran secret dan memastikan pipeline tidak
bergantung pada credential nyata?

**Q5.** Temuan mana yang memblokir rilis, mana dapat diterima sementara, dan
mengapa? Sertakan pemilik serta tenggat.

**Q6.** Apa perubahan minimum pada source/dependensi/konfigurasi/gate yang
mengatasi akar masalah tanpa mematikan fungsi normal?

**Q7.** Artefak SBOM atau provenance apa yang dihasilkan, dan apakah ia benar-benar
mengacu pada build yang diuji?

**Q8.** Paket/versi mana ada di lockfile dan SBOM, dan apakah selaras?

**Q9.** Bagaimana pipeline menangani string rahasia dummy dan temuan image yang
relevan?

**Q10.** Bukti apa menunjukkan API tetap berjalan sesudah gate diperketat?

**Q11.** Jalankan gate pada satu fixture holdout dari mentor. Apakah keputusan
rilis sesuai ekspektasi? Jelaskan false positive/negative dan prosedur override
yang disetujui.

**Q12.** Apa kebijakan rilis final, bukti yang disimpan, batas yang belum tertutup,
dan prioritas perbaikan 30 hari?

## Bukti yang diserahkan

- `build/gate-result.json` baseline dan setelah perbaikan.
- Diff remediasi (source, lockfile, Dockerfile, konfigurasi).
- SBOM dan `sbom_subject_digest`.
- Output uji fungsi normal sebelum/sesudah.
- Runbook rilis dan catatan override (jika ada).
- Analisis batas: apa yang tidak dibuktikan oleh gate ini.

## Batas

- Jangan memakai token nyata, registry publik, atau menjalankan paket berbahaya.
- Semua paket dan advisory bersifat fiktif; jangan mengunduh dependensi.
- Jangan mematikan seluruh kategori scanner untuk membuat gate hijau.
