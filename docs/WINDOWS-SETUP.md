# Windows setup — DevSecOps labs (no Python required)

## Prerequisites

- Windows 10/11 with PowerShell 5.1+.
- PHP 8.1+ with `curl`, `json`, `hash`, `zip`. XAMPP PHP
  (`C:\xampp\php\php.exe`) is detected automatically.
- No Docker, Composer, Python, registry access, or internet is required.

## DEV-1 commands

```powershell
cd cases\dev-1-campusapi

# Baseline gate (sengaja gagal)
.\tools\gate.ps1 -RepoPath .

# After your changes
php tools\build.php .
php tools\make-sbom.php .
.\tools\gate.ps1 -RepoPath .

# Freeze the gate and policy before holdout
.\tools\gate.ps1 -RepoPath . -Freeze
```

The functional stage starts the API on `127.0.0.1:8096` and stops it afterwards.
Use `-Port 8100` if the port is busy.

## DEV-2 commands

```powershell
cd cases\dev-2-buildgate

# Generate inert fixtures (instructor step)
php tools\build-fixtures.php

# Starter check (digest only; intentionally incomplete)
.\tools\gate-starter.ps1 -FixtureDir .\fixtures\tampered

# Reference gate on each fixture
.\tools\gate.ps1 -FixtureDir .\fixtures\tampered
.\tools\gate.ps1 -FixtureDir .\fixtures\wrong-source
.\tools\gate.ps1 -FixtureDir .\fixtures\stale
```

## Instructor verification

```powershell
cd instructor-private\dev-1
.\verify.ps1

cd ..\dev-2
.\verify.ps1
```

DEV-1 proves baseline fail, corrected pass, deterministic build, stale-SBOM
rejection, weakened-policy rejection, and root-runtime rejection. DEV-2 proves
tampered/wrong-source/stale/untrusted/missing-builder rejection and golden
artifact acceptance.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| `PHP 8.1+ not found` | Add `C:\xampp\php` to `PATH` or install PHP. |
| Port 8096 busy | Add `-Port 8100`. |
| `running scripts is disabled` | Use `powershell -ExecutionPolicy Bypass -File .\tools\gate.ps1 ...`. |
| SBOM mismatch | Rebuild, then rerun `php tools\make-sbom.php .`. |
| Gate passes unexpectedly | Check that `block_severities` is intact and the SBOM is fresh. |

## Measured footprint (2026-11-10)

- Artifact manifest: a few KB; no container image is built.
- Gate runtime: a few seconds; the API is served by PHP's built-in server.
- Host: Windows 11, 7.8 GB RAM, PHP 8.2.4.
