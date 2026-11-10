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

## Not built yet

- DEV-2 BuildGate provenance (feature 003): library source, inert archives,
  hash manifest, build log, provenance statement, tamper/stale/wrong-source
  fixtures, golden artifact acceptance, package.
- Student package zip for DEV-1 (files are ready; a packaging script with
  manifest and exclusion checks is still needed).
- Blind pilot and resource measurement for the DevSecOps cases.
- Cross-case difficulty calibration.

## How to verify DEV-1

```powershell
cd cases\dev-1-campusapi
.\tools\gate.ps1 -RepoPath .          # expect fail, four reasons
cd ..\..\instructor-private\dev-1
.\verify.ps1                          # expect 13/13 pass
```
