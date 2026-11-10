#requires -Version 5.1
<#
Package the DEV-2 student distribution: one case, public fixtures, starter gate.
Excludes the reference gate, golden artifact, and instructor keys.
#>
param(
    [string]$CaseRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'
$CaseRoot = (Resolve-Path $CaseRoot).Path
$repoRoot = Split-Path -Parent (Split-Path -Parent $CaseRoot)
$packageDir = Join-Path $repoRoot 'packages'
$staging = Join-Path $packageDir '.staging-dev-2-buildgate'
$zip = Join-Path $packageDir 'dev-2-buildgate-student.zip'

if (-not (Test-Path (Join-Path $CaseRoot 'fixtures\tampered\library-1.0.0.zip'))) {
    throw 'Fixtures missing. Run: php tools\build-fixtures.php'
}
if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
New-Item -ItemType Directory -Path $staging -Force | Out-Null

foreach ($item in @('lib', 'policy', 'fixtures')) {
    Copy-Item (Join-Path $CaseRoot $item) (Join-Path $staging $item) -Recurse -Force
}
foreach ($doc in @('README-mahasiswa.md', 'student-card.md')) {
    Copy-Item (Join-Path $CaseRoot $doc) (Join-Path $staging $doc) -Force
}
New-Item -ItemType Directory -Path (Join-Path $staging 'tools') -Force | Out-Null
Copy-Item (Join-Path $CaseRoot 'tools\gate-starter.ps1') (Join-Path $staging 'tools\gate-starter.ps1') -Force

# Prior gate results contain expected decisions and must never ship.
Get-ChildItem (Join-Path $staging 'fixtures') -Recurse -Filter 'gate-result.json' -ErrorAction SilentlyContinue |
    Remove-Item -Force

# Manifest over the exact packaged file set.
$lines = New-Object System.Collections.Generic.List[string]
$files = Get-ChildItem $staging -Recurse -File | Sort-Object { $_.FullName.Substring($staging.Length).Replace('\', '/') }
foreach ($file in $files) {
    $rel = $file.FullName.Substring($staging.Length).TrimStart('\').Replace('\', '/')
    $hash = (Get-FileHash $file.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
    $lines.Add("$hash  $rel")
}
[System.IO.File]::WriteAllLines((Join-Path $staging 'manifest-sha256.txt'), $lines, (New-Object System.Text.UTF8Encoding($false)))

if (Test-Path $zip) { Remove-Item $zip -Force }
Compress-Archive -Path (Join-Path $staging '*') -DestinationPath $zip -CompressionLevel Optimal
Remove-Item $staging -Recurse -Force

$hash = (Get-FileHash $zip -Algorithm SHA256).Hash.ToLowerInvariant()
[System.IO.File]::WriteAllText("$zip.sha256", "$hash  dev-2-buildgate-student.zip`n", (New-Object System.Text.UTF8Encoding($false)))
Write-Output "Package: $zip"
Write-Output "SHA256:  $hash"
Write-Output "Files:   $($lines.Count + 1)"
