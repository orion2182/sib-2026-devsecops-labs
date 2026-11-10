# DevSecOps & Supply Chain Labs 2026 — workspace pembangunan

Repository publik ini menyiapkan lab untuk **2 kelompok SIB** dengan dua keputusan rilis yang berbeda. Kelompok DEV-1 menangani keamanan pipeline CampusAPI; kelompok DEV-2 memverifikasi integritas artefak dan provenance BuildGate. Kalender tetap 20 November–23 Desember 2026. Latar riset, Q1–Q12, rubrik, dan fase ada di `docs/reference/Riset-Lab-DevSecOps-Supply-Chain-2026.md` serta [repo perencanaan](https://github.com/orion2182/sib-2026-cybersecurity-final-project).

**Status per 10 November 2026:** Spec Kit 1.2.3, integrasi OpenCode/Codex, konstitusi, dan tiga spesifikasi tersedia. **Repo kasus, pipeline, scanner fixture, artefak golden, holdout, dan pilot belum dibuat.**

## Dua kasus yang harus dibangun

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
| `shared/`, `cases/` | Lokasi implementasi saat dibangun |
| `.opencode/commands/` | Perintah Spec Kit untuk OpenCode |
| `scripts/select_feature.py` | Pemilih fitur aktif lokal |

## Mulai di laptop lain

Prasyarat awal: Git, Python 3.11+, dan OpenCode. Kebutuhan container runtime, bahasa API, dan scanner diputuskan di tahap rencana berdasarkan pilot kompatibilitas; jangan menganggapnya sudah terpasang.

```bash
git clone https://github.com/orion2182/sib-2026-devsecops-labs.git
cd sib-2026-devsecops-labs
python3 scripts/select_feature.py 001-release-evaluation-harness
opencode
```

Pada Windows gunakan `py -3 scripts/select_feature.py 001-release-evaluation-harness` bila perlu. Di chat OpenCode, jalankan bertahap:

```text
/speckit.plan
/speckit.checklist
/speckit.tasks
/speckit.analyze
/speckit.implement
/speckit.converge
```

Perintah sudah ada pada `.opencode/commands/`, sehingga `specify init` tidak perlu diulang. Untuk mengganti fitur, lihat `python3 scripts/select_feature.py --list`, pilih fitur, lalu mulai sesi baru. Urutan pembangunan: runner → DEV-1 → DEV-2 → pilot buta. [Referensi integrasi OpenCode Spec Kit](https://github.github.com/spec-kit/integrations/opencode.html).

## Gerbang rilis mentor

1. Baseline gagal secara deterministik pada kondisi yang disengaja; alasan kegagalan dan versi input/tool tercatat.
2. Perbaikan atau policy baru menghasilkan pass tanpa menonaktifkan seluruh kategori pemeriksaan; uji fungsi normal tetap lulus.
3. Manifest mengikat source revision, lockfile, build, SBOM/provenance, dan digest artefak yang benar-benar diuji.
4. Known-bad dan known-good fixture menghasilkan keputusan yang berbeda sesuai alasan; holdout privat digunakan setelah gate mahasiswa dibekukan.
5. Paket tiap kelompok hanya memuat repo/fixture kasusnya. Golden artifact, jawaban, kunci, token valid, dan hasil holdout tidak masuk GitHub publik atau paket peserta.
6. Tidak ada publikasi ke registry, eksekusi paket tidak tepercaya, atau akses sistem produksi. Pilot pada laptop 8 GB mengukur RAM dan waktu; runner mentor menjadi fallback bila perlu.

Repository ini adalah bahan pembangunan, bukan handout mahasiswa. Simpan semua materi penilaian privat di luar GitHub publik.
