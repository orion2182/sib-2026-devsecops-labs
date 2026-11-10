# DevSecOps & Supply Chain Labs 2026 — workspace pembangunan

Repository publik ini menyiapkan lab untuk **2 kelompok SIB** dengan dua keputusan rilis yang berbeda. Kelompok DEV-1 menangani keamanan pipeline CampusAPI; kelompok DEV-2 memverifikasi integritas artefak dan provenance BuildGate. Kalender tetap 20 November–23 Desember 2026. Latar riset, Q1–Q12, rubrik, dan fase ada di `docs/reference/Riset-Lab-DevSecOps-Supply-Chain-2026.md` serta [repo perencanaan](https://github.com/orion2182/sib-2026-cybersecurity-final-project).

**Status per 10 November 2026:** 2 kasus DevSecOps dan gate PHP/PowerShell telah dibangun; DEV-1 lulus 13/13 dan DEV-2 lulus 20/20 pemeriksaan instruktur. Pilot buta manusia dan pemeriksaan rilis masih diperlukan. Lihat `docs/BUILD-STATUS.md` dan `../README-TRANSFER.md` pada salinan lengkap.

## Dua kasus yang tersedia

| Kasus | Artefak utama | Pertanyaan keputusan | Bukti hasil |
| --- | --- | --- | --- |
| DEV-1 — CampusAPI release gate | Repo API kecil, lockfile, Dockerfile/config, SCA/SAST/secret scan, SBOM | Temuan mana memblokir rilis dan perubahan minimum apa memperbaikinya? | Gate gagal sebelum lalu lulus sesudah, SBOM cocok dengan build, fungsi API normal tetap lulus |
| DEV-2 — BuildGate provenance | Repo library kecil, arsip paket inert, hash, log build, attestation/provenance | Di mana rantai source→build→artefak terputus dan bagaimana gate menolak perubahan? | Gate menolak artefak tampered/wrong-source/stale dan menerima golden artifact |

Keduanya wajib memakai input dan versi tool yang terkunci, keputusan pass/fail dengan alasan, dan holdout privat dari mentor. Untuk DEV-1, fokus pada triase scanner dan release gate. Untuk DEV-2, fokus pada digest, asal build, identitas builder, dan trust policy. SBOM menggunakan SPDX atau CycloneDX bila relevan; provenance menggunakan konsep SLSA. Jangan menganggap output scanner atau status hijau CI sebagai jawaban tanpa memeriksa artefak yang diuji.

## Peta file untuk OpenCode

| File/direktori | Fungsi |
| --- | --- |
| `AGENTS.md` | Instruksi proyek otomatis untuk OpenCode |
| `.specify/memory/constitution.md` | Aturan sumber, build, gate, keamanan, dan rilis |
| `specs/001-release-evaluation-harness/spec.md` | Runner bersama, manifest, fail/pass, holdout, packaging |
| `specs/002-campusapi-release-gate/spec.md` | Kontrak DEV-1 |
| `specs/003-buildgate-provenance/spec.md` | Kontrak DEV-2 |
| `docs/reference/Riset-Lab-DevSecOps-Supply-Chain-2026.md` | Task, pertanyaan, rubrik, timeline, dan sumber riset |
| `shared/`, `cases/` | Implementasi runner dan kasus |
| `.opencode/commands/` | Perintah Spec Kit untuk OpenCode |
| `scripts/select-feature.ps1` | Pemilih fitur di Windows tanpa Python; `scripts/select_feature.py` opsional |

## Menjalankan dan mengembangkan di Windows

Salinan lengkap dari flashdisk memiliki `README-TRANSFER.md` di direktori induk. Jalankan lab dari disk lokal Windows dengan PowerShell 5.1+ dan PHP 8.1+; **Python dan Docker tidak diperlukan** untuk implementasi sekarang. Instruksi menjalankan, menguji, dan mengemas kasus ada di `docs/WINDOWS-SETUP.md` dan `docs/BUILD-STATUS.md`.

Untuk perubahan spesifikasi di OpenCode, jalankan dari root repo ini:

```powershell
.\scripts\select-feature.ps1 --list
.\scripts\select-feature.ps1 002-campusapi-release-gate
opencode
```

Perintah `/speckit.plan`, `/speckit.checklist`, `/speckit.tasks`, `/speckit.analyze`, `/speckit.implement`, dan `/speckit.converge` dipakai **di chat OpenCode** ketika mengubah fitur, bukan untuk menjalankan lab. `scripts/select_feature.py` tersedia hanya sebagai alternatif jika Python memang terpasang. Paket peserta harus dibuat melalui perintah packaging dan diperiksa sebelum dibagikan; direktori builder ini memuat bahan instruktur.

## Gerbang rilis mentor

1. Baseline gagal secara deterministik pada kondisi yang disengaja; alasan kegagalan dan versi input/tool tercatat.
2. Perbaikan atau policy baru menghasilkan pass tanpa menonaktifkan seluruh kategori pemeriksaan; uji fungsi normal tetap lulus.
3. Manifest mengikat source revision, lockfile, build, SBOM/provenance, dan digest artefak yang benar-benar diuji.
4. Known-bad dan known-good fixture menghasilkan keputusan yang berbeda sesuai alasan; holdout privat digunakan setelah gate mahasiswa dibekukan.
5. Paket tiap kelompok hanya memuat repo/fixture kasusnya. Golden artifact, jawaban, kunci, token valid, dan hasil holdout tidak masuk GitHub publik atau paket peserta.
6. Tidak ada publikasi ke registry, eksekusi paket tidak tepercaya, atau akses sistem produksi. Pilot pada laptop 8 GB mengukur RAM dan waktu; runner mentor menjadi fallback bila perlu.

Repository ini adalah bahan pembangunan, bukan handout mahasiswa. Simpan semua materi penilaian privat di luar GitHub publik.
