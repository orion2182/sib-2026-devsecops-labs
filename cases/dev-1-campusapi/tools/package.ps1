#requires -Version 5.1
<#
Package the DEV-1 student distribution. Ships the baseline only: no built
artifact, no SBOM, no corrected overlay, no holdout expectations.
#>
param(
    [string]$CaseRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'
$CaseRoot = (Resolve-Path $CaseRoot).Path
$repoRoot = Split-Path -Parent (Split-Path -Parent $CaseRoot)
$packageDir = Join-Path $repoRoot 'packages'
$staging = Join-Path $packageDir '.staging-dev-1-campusapi'
$zip = Join-Path $packageDir 'dev-1-campusapi-student.zip'

if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
New-Item -ItemType Directory -Path $staging -Force | Out-Null

$items = @('app', 'policy', 'scan', 'tools', 'composer.json', 'composer.lock', 'Dockerfile', 'SOURCE_REVISION', 'README-mahasiswa.md', 'student-card.md')
foreach ($item in $items) {
    $source = Join-Path $CaseRoot $item
    if (Test-Path $source) {
        Copy-Item $source (Join-Path $staging $item) -Recurse -Force
    }
}

# Never ship prior gate output or a generated SBOM.
foreach ($generated in @('build', 'sbom')) {
    $path = Join-Path $staging $generated
    if (Test-Path $path) { Remove-Item $path -Recurse -Force }
}
Get-ChildItem $staging -Recurse -Filter 'gate-result.json' -ErrorAction SilentlyContinue | Remove-Item -Force
Get-ChildItem $staging -Recurse -Filter 'freeze.json' -ErrorAction SilentlyContinue | Remove-Item -Force

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
[System.IO.File]::WriteAllText("$zip.sha256", "$hash  dev-1-campusapi-student.zip`n", (New-Object System.Text.UTF8Encoding($false)))
Write-Output "Package: $zip"
Write-Output "SHA256:  $hash"
Write-Output "Files:   $($lines.Count + 1)"
