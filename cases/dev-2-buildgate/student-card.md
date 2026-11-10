# Kartu kasus DEV-2 - BuildGate provenance

**Untuk:** kelompok DevSecOps-2 saja. **Kalender:** 20 November-18 Desember 2026.
**Kasus:** integritas artefak dan asal build, bukan pemindaian kerentanan.

## Situasi

Sebuah library fiktif `acme/textkit` dikemas menjadi arsip dan disertai
attestation provenance. Sebagian arsip diubah setelah build, sebagian attestation
menunjuk source atau builder yang salah, dan sebagian sudah kedaluwarsa. Tim
diminta membangun gate yang hanya menerima artefak yang terikat pada source,
lockfile, builder, dan digest yang benar.

## Tujuan

1. Menjelaskan trust root dan klaim yang benar-benar diperiksa.
2. Menemukan titik pertama di mana digest/referensi tidak cocok.
3. Menentukan field provenance yang wajib.
4. Membangun gate yang menolak tampered/stale/wrong-source/untrusted dan menerima
   golden artifact.
5. Menjelaskan apa yang **tidak** dibuktikan oleh attestation.
6. Menjalankan gate pada fixture holdout mentor.

## Artefak

| Path | Isi |
| --- | --- |
| `lib/` | Source, lockfile, `SOURCE_REVISION` |
| `policy/trust-policy.json` | Trusted builders, subject name, batas umur |
| `fixtures/*/library-1.0.0.zip` | Arsip inert |
| `fixtures/*/attestation.json` | Statement provenance |
| `fixtures/*/sha256.txt` | Manifest fixture |
| `tools/gate-starter.ps1` | Pemeriksaan digest awal |

## Q1-Q12

**Q1.** Apa paket, source revision, lockfile, artefak akhir, dan pihak yang
berwenang menyetujui rilis?

**Q2.** Tiga risiko supply chain terpenting yang dapat diuji dari fixture;
pisahkan fakta dan asumsi.

**Q3.** Pada fixture awal, pemeriksaan mana lulus dan gagal? Catat input, output,
dan satu false positive potensial.

**Q4.** Bagaimana Anda memastikan gate tidak bergantung pada kredensial nyata atau
publikasi registry?

**Q5.** Temuan mana yang memblokir rilis, mana dapat diterima sementara, dan
mengapa? Sertakan pemilik dan tenggat.

**Q6.** Apa perubahan minimum pada gate/kebijakan yang mengatasi akar masalah tanpa
mematikan pemeriksaan penting?

**Q7.** Provenance/SBOM apa yang dihasilkan, dan apakah ia mengacu pada artefak
yang benar-benar diuji?

**Q8.** Di titik mana digest source, build, dan arsip mulai tidak cocok? Tunjukkan
perbandingan dan event/fixture yang membuktikannya.

**Q9.** Field provenance mana yang diperlukan untuk mengaitkan artefak dengan
source/build yang sah? Jelaskan trust root dan batas klaim.

**Q10.** Apakah gate menolak artefak yang diubah dan menerima golden artifact?
Tunjukkan dua keputusan beserta reason code dan digest.

**Q11.** Jalankan gate pada satu fixture holdout mentor. Apakah keputusan sesuai
ekspektasi? Jelaskan false positive/negative dan prosedur override.

**Q12.** Apa kebijakan rilis final, bukti yang disimpan, batas yang belum tertutup,
dan prioritas perbaikan 30 hari?

## Bukti yang diserahkan

- Gate/aturan final dan hash-nya.
- Keputusan per fixture: pass/fail, reason code, digest artefak dan subject.
- Perbandingan attestation vs source revision vs lockfile.
- Keputusan pada golden artifact dan holdout mentor.
- Runbook rilis dan pernyataan batas (mis. signing key, reproducibility).

## Batas

- Jangan mengeksekusi arsip atau mengunduh paket.
- Jangan memakai registry publik atau kunci signing nyata.
- Kesamaan hash file saja bukan bukti asal tepercaya.
