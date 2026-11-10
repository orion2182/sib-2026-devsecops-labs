#requires -Version 5.1
<#
.SYNOPSIS
Starter artifact check for DEV-2. This is intentionally incomplete.

.DESCRIPTION
It compares the archive digest with the shipped manifest. It does NOT verify
origin, source revision, builder identity, lockfile binding, or freshness.
Extend it into a provenance gate.

.EXAMPLE
.\tools\gate-starter.ps1 -FixtureDir .\fixtures\tampered
#>
param(
    [Parameter(Mandatory = $true)]
    [string]$FixtureDir
)

$ErrorActionPreference = 'Stop'
$FixtureDir = (Resolve-Path $FixtureDir).Path
$artifact = Join-Path $FixtureDir 'library-1.0.0.zip'
$manifestPath = Join-Path $FixtureDir 'sha256.txt'

if (-not (Test-Path $artifact)) { Write-Output 'FAIL artifact missing'; exit 1 }
if (-not (Test-Path $manifestPath)) { Write-Output 'FAIL manifest missing'; exit 1 }

$digest = (Get-FileHash $artifact -Algorithm SHA256).Hash.ToLowerInvariant()
$manifest = Get-Content $manifestPath -Raw
$matches = $manifest -match [regex]::Escape($digest)

Write-Output "artifact sha256: $digest"
Write-Output "manifest match:  $matches"
Write-Output ''
Write-Output 'WARNING: a matching file hash is not proof of trusted origin.'
Write-Output 'Add checks for signature, key fingerprint, subject name, builder, source revision, lockfile, and freshness.'

if (-not $matches) { exit 1 }
exit 0
