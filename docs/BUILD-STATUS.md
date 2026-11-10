# Build status — DevSecOps labs

Updated 2026-11-10.

## Implemented and verified

### Shared release-evaluation harness

- Deterministic artifact build: canonical manifest of source, lockfile, and
  Dockerfile hashes; digest has no timestamps.
- Six-stage gate: preflight, build, security, SBOM, functional, artifact.
- Reason codes for dependency, secret, container, SBOM, functional, artifact, and
  policy findings.
- SBOM (CycloneDX 1.5) bound to the exact artifact digest.
- Freeze record with gate/policy/advisories/lockfile/artifact/SBOM hashes.
- Python and Docker are not required.

### DEV-1 CampusAPI release gate (feature 002)

- Small PHP API: health, course list, student lookup, enrollment, duplicate
  rejection.
- Baseline gate fails with `DEP_ACME-2026-0001`, `SECRET_IN_SOURCE`,
  `CONTAINER_ROOT_USER`, `SBOM_MISSING`; functional checks pass.
- Corrected build passes with the SBOM subject equal to the artifact digest.
- Deterministic rebuild produces the same digest.
- Holdout variants rejected: stale SBOM (`SBOM_SUBJECT_MISMATCH`), weakened policy
  (`POLICY_WEAKENED`), root runtime (`CONTAINER_ROOT_USER`).
- `instructor-private/dev-1/verify.ps1`: 13/13 checks pass.
- Scanner reports include a stale SCA finding and a SAST false positive to force
  triage rather than blind trust.

### DEV-2 BuildGate provenance (feature 003)

- Synthetic `acme/textkit` library, pinned lockfile, source revision, and trust
  policy with a trusted builder and a 30-day attestation limit.
- Deterministic inert archive plus SLSA-shaped attestations for: golden,
  tampered, wrong source, stale, untrusted builder, and missing builder.
- Gate binds subject name/digest, builder, source revision, lockfile digest,
  freshness, and a detached RSA signature over the canonical attestation. A
  self-consistent file hash alone is not accepted.
- Verifications: 20/20 pass, including unsigned, bad-signature, and untrusted-key rejection.
- Student package (24 files) verified with no golden artifact, reference gate,
  expected outcomes, or prior gate results.

### Student packages

- DEV-1: 22 files, no build, SBOM, corrected overlay, or prior results. The
  packaged baseline still fails with the four designed reasons.
- DEV-2: 24 files, starter gate only.

## Theme status

Both DevSecOps cases are implemented, tested, and packaged.

## Remaining before release

- Pilot buta manusia dan kalibrasi dua kasus pada laptop perwakilan.
- Pemeriksaan final paket peserta dan fallback mentor.
- DEV-2 kini memakai tanda tangan RSA-2048 lokal dengan verifikasi signature detached; lihat `instructor-private/dev-2/verify.ps1`. Kunci privat tetap di luar Git.

## How to verify DEV-1

```powershell
cd cases\dev-1-campusapi
.\tools\gate.ps1 -RepoPath .          # expect fail, four reasons
cd ..\..\instructor-private\dev-1
.\verify.ps1                          # expect 13/13 pass
```
