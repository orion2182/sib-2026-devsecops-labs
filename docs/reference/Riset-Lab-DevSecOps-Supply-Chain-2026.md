# Riset dan rancangan final project DevSecOps & keamanan supply chain

**Status:** rancangan instruktur, 26 Oktober 2026. **Peserta:** 2 kelompok, 2 repositori simulasi berbeda.  
**Kalender:** 20 November–23 Desember 2026; hari mentorship, gladi, dan presentasi tidak berubah.

## 1. Keputusan desain berbasis riset

Gunakan [NIST SSDF SP 800-218](https://csrc.nist.gov/pubs/sp/800/218/final) untuk praktik pembangunan perangkat lunak aman. Untuk integritas rantai pasok, gunakan [SLSA v1.2](https://slsa.dev/spec/v1.2/) sebagai model provenance dan [SPDX](https://spdx.dev/use/specifications/) atau [CycloneDX](https://cyclonedx.org/specification/overview/) sebagai format SBOM bila relevan. Tema ini menilai keputusan **pipeline gate, dependensi, artefak, dan provenance**, bukan sekadar menjalankan scanner atau menyerang aplikasi seperti VAPT.

Sediakan dua repositori source sintetis, pipeline lokal atau CI terisolasi, paket/artefak dummy, serta baseline yang gagal lalu versi perbaikan. Semua credential adalah string dummy yang jelas tidak berlaku. Tidak ada penerbitan paket ke registry publik, token cloud nyata, atau eksekusi kode tak tepercaya dari paket berbahaya. Scan dijalankan pada fixture terkunci; hasil scanner perlu ditriase agar peserta tidak menilai semua alert sama penting. Target laptop 8 GB **bersyarat setelah pilot**; bila build/scanner terlalu berat, mentor menyediakan runner terpusat.

## 2. Dua kasus yang berbeda

| Kelompok | Narasi | Aset/fixture mentor | Fokus keputusan |
| --- | --- | --- | --- |
| DEV-1 — **CampusAPI release gate** | Tim pengembang hendak merilis API kecil; dependensi, konfigurasi image, dan string rahasia dummy menimbulkan alarm | Repo API, lockfile, Dockerfile, pipeline awal, hasil SCA/SAST/secret scan, image/manifest SBOM | Pilih temuan yang benar-benar memblokir rilis, perbaiki akar masalah, hasilkan SBOM, dan buktikan pipeline gagal sebelum serta lulus sesudah perbaikan |
| DEV-2 — **BuildGate provenance** | Artefak paket fiktif berubah di antara source dan distribusi; asal build belum jelas | Repo library kecil, lockfile, arsip paket sintetis, hash manifest, log CI, contoh attestation/provenance, fixture dependensi lokal | Deteksi mismatch source–artefak, tetapkan verifikasi digest/provenance, dan rancang gate yang menolak artefak tidak tepercaya tanpa membangun/menerbitkan kode berbahaya |

Keduanya mengerjakan secure development dan supply chain, tetapi artefak serta keputusan final berbeda. DEV-1 berpusat pada pengelolaan kelemahan dalam pipeline; DEV-2 pada integritas artefak dan asal build. Paket peserta tidak memuat jawaban/golden artifact; mentor menyimpan expected pass/fail dan fixture holdout.

## 3. Lab bertahap dan pertanyaan mahasiswa

### Task 1 — Kontrak rilis (20–23 Nov)

**Situasi:** tim mengambil alih rilis software fiktif. Mereka harus menentukan apa yang boleh keluar dan bukti apa yang wajib menyertai artefak.

- **Q1.** Apa aplikasi/paket, dependensi, jalur build, artefak akhir, dan pihak yang berwenang menyetujui rilis?
- **Q2.** Tiga risiko supply chain terpenting apa yang dapat diuji dari fixture yang tersedia? Pisahkan fakta dan asumsi.

### Task 2 — Baseline pipeline (24–27 Nov)

- **Q3.** Pada commit/fixture awal, gate mana lulus dan gagal? Catat versi tool, input, output, serta satu false positive potensial.
- **Q4.** Bagaimana Anda akan mencegah kebocoran secret dan memastikan pipeline tidak bergantung pada credential nyata?

### Task 3 — Triase dan perbaikan (30 Nov–8 Des)

- **Q5.** Temuan mana yang memblokir rilis, mana dapat diterima sementara, dan mengapa? Sertakan pemilik serta tenggat.
- **Q6.** Apa perubahan minimum pada source/dependensi/konfigurasi/gate yang mengatasi akar masalah tanpa mematikan fungsi normal?
- **Q7.** Artefak SBOM atau provenance apa yang dihasilkan, dan apakah ia benar-benar mengacu pada build yang diuji?

**Pertanyaan khusus kasus:**

| Kasus | Q8 | Q9 | Q10 |
| --- | --- | --- | --- |
| DEV-1 | Paket/versi mana ada di lockfile dan SBOM, dan apakah selaras? | Bagaimana pipeline menangani string rahasia dummy dan temuan image yang relevan? | Bukti apa menunjukkan API tetap berjalan sesudah gate diperketat? |
| DEV-2 | Di titik mana digest source, build, dan arsip mulai tidak cocok? | Field provenance mana diperlukan untuk mengaitkan artefak dengan source/build yang sah? | Apakah gate menolak artefak yang diubah namun menerima golden artifact? |

**Bukti Q5–Q10:** diff terpilih, output gate sebelum/sesudah, SBOM/provenance, hash artefak, dan hasil uji fungsi normal. Jangan menilai hasil hanya dari warna hijau CI.

### Task 4 — Uji release gate (9–11 Des)

- **Q11.** Jalankan gate pada satu fixture holdout dari mentor. Apakah keputusan rilis sesuai ekspektasi? Jelaskan false positive/false negative dan prosedur override yang disetujui.

### Task 5 — Laporan dan demo (14–18 Des)

- **Q12.** Apa kebijakan rilis final, bukti yang disimpan, batas yang belum tertutup, dan prioritas perbaikan 30 hari?

**Serahkan 18 Des:** laporan triase, aturan gate, diff remediasi, SBOM/provenance sesuai kasus, log sebelum/sesudah, hasil holdout, runbook rilis, dan slide. Presentasi 21–23 Des mencakup demo gate dan pembelaan individu.

## 4. Timeline fase dan gerbang

| Tanggal | Pekerjaan dan gerbang |
| --- | --- |
| 20–23 Nov | Aset, kontrak rilis, akses repo/runner; **G0: baseline dapat direproduksi** |
| 24–27 Nov | Inventaris dependensi dan hasil gate awal; **G1: risiko diprioritaskan** |
| 30 Nov–2 Des | Triase scanner dan rencana perubahan; **G2: keputusan gate dengan alasan** |
| 3–8 Des | Perbaikan dan bukti SBOM/provenance; **G3: before/after + fungsi normal** |
| 9–11 Des | Holdout, override, kebijakan rilis; **G4: gate teruji** |
| 14–18 Des | Laporan dan gladi 16–18 Des; **G5: paket final** |
| 21–23 Des | Presentasi/pengujian sesuai slot |

## 5. Rubrik dan persiapan mentor

| Komponen | Poin |
| --- | ---: |
| Inventaris source, dependensi, artefak, dan alur build | 15 |
| Triase temuan dan keputusan risiko | 20 |
| Perbaikan serta fungsi normal | 20 |
| SBOM/provenance dan integritas artefak | 20 |
| Gate, holdout, override, dan runbook rilis | 15 |
| Tanya jawab kontribusi individu | 10 |
| **Total** | **100** |

**Checklist mentor sebelum 20 Nov:** buat dua repo/fixture yang benar-benar berbeda; pin versi tool dan dependensi; siapkan pipeline yang gagal karena kondisi yang disengaja; sediakan golden artifact dan holdout tampered untuk DEV-2; validasi SBOM/provenance terhadap hash nyata; uji gate gagal/lulus serta fungsi normal; pastikan tidak ada credential nyata atau publikasi ke registry; ukur runtime dan RAM pada laptop 8 GB, siapkan runner fallback; simpan answer key dan hasil holdout di sisi instruktur; pilot buta kedua kasus.

**Batas saat ini:** repositori kasus, pipeline, scanner fixture, dan golden artifact belum dibangun. Tool spesifik dan ambang gate diputuskan setelah pilot kompatibilitas, tanpa mengubah tujuan belajar. Kalender harian terperinci tercantum pada Google Sheets mentor lintas tema.
