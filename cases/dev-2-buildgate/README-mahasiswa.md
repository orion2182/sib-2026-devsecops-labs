# BuildGate - paket lab DEV-2

Paket ini hanya untuk kelompok DevSecOps-2. Seluruh source, paket, attestation,
builder, dan alamat bersifat sintetis. Arsip bersifat inert dan tidak pernah
dieksekusi.

## Prasyarat

- Windows 10/11, PowerShell 5.1+.
- PHP 8.1+ (`json`, `hash`, `zip`). XAMPP terdeteksi otomatis.
- Tidak perlu Docker, Composer, Python, registry, atau internet.

## Isi paket

| Path | Fungsi |
| --- | --- |
| `lib/` | Source library fiktif dan lockfile |
| `policy/trust-policy.json` | Trust root dan aturan |
| `fixtures/` | Arsip inert + attestation untuk diuji |
| `tools/gate-starter.ps1` | Pemeriksaan digest awal (belum lengkap) |
| `student-card.md` | Kasus dan Q1-Q12 |

## Menjalankan

```powershell
# Pemeriksaan awal (hanya digest)
powershell -ExecutionPolicy Bypass -File .\tools\gate-starter.ps1 -FixtureDir .\fixtures\tampered

# Setelah gate Anda lengkap, jalankan pada setiap fixture
# dan pada golden artifact yang diberikan mentor.
```

## Yang harus Anda bangun

Perluas `gate-starter.ps1` (atau tulis gate baru) sehingga memeriksa:

1. digest arsip terhadap manifest;
2. nama subject dan digest pada attestation;
3. builder ada dan termasuk trust policy;
4. source revision sama dengan `lib/SOURCE_REVISION`;
5. digest lockfile sama dengan yang tercatat;
6. attestation belum kedaluwarsa.

Gate harus **menolak** arsip yang diubah, attestation kedaluwarsa, source yang
salah, dan builder tak tepercaya; serta **menerima** golden artifact dari mentor.

## Troubleshooting

| Gejala | Tindakan |
| --- | --- |
| `running scripts is disabled` | Gunakan `powershell -ExecutionPolicy Bypass -File ...`. |
| Fixtures tidak ada | Minta paket lengkap; fixtures dihasilkan instruktur. |
| Semua fixture tampak sama | Bandingkan `attestation.json`, bukan hanya arsip. |
| Gate menerima tampered | Periksa apakah Anda hanya membandingkan hash file. |
