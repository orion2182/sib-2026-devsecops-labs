#requires -Version 5.1
<#
.SYNOPSIS
CampusAPI release gate (DEV-1).

.DESCRIPTION
Runs preflight, build, security, SBOM, functional, and artifact stages and
writes a machine-readable decision. The gate recomputes dependency, secret, and
container findings from pinned inputs; scanner reports are evidence, not truth.

.EXAMPLE
.\tools\gate.ps1 -RepoPath .
.\tools\gate.ps1 -RepoPath . -Freeze
#>
param(
    [string]$RepoPath = (Split-Path -Parent $PSScriptRoot),
    [int]$Port = 8096,
    [switch]$Freeze,
    [string]$Out = ''
)

$ErrorActionPreference = 'Stop'
$RepoPath = (Resolve-Path $RepoPath).Path
$gateVersion = '1.0.0'

function Get-PhpPath {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    foreach ($candidate in @('C:\xampp\php\php.exe', "$env:ProgramFiles\php\php.exe", "${env:ProgramFiles(x86)}\php\php.exe")) {
        if ($candidate -and (Test-Path $candidate)) { return $candidate }
    }
    throw 'PHP 8.1+ not found.'
}

function Compare-Ver([string]$Left, [string]$Right) {
    $a = @($Left.Split('.') | ForEach-Object { [int]($_ -replace '[^0-9].*$', '') })
    $b = @($Right.Split('.') | ForEach-Object { [int]($_ -replace '[^0-9].*$', '') })
    $len = [Math]::Max($a.Count, $b.Count)
    for ($i = 0; $i -lt $len; $i++) {
        $x = if ($i -lt $a.Count) { $a[$i] } else { 0 }
        $y = if ($i -lt $b.Count) { $b[$i] } else { 0 }
        if ($x -lt $y) { return -1 }
        if ($x -gt $y) { return 1 }
    }
    return 0
}

function Test-Affected([string]$Version, [string]$Range) {
    if ($Range -match '^(<=|>=|<|>|=)\s*(.+)$') {
        $cmp = Compare-Ver $Version $Matches[2]
        switch ($Matches[1]) {
            '<'  { return $cmp -lt 0 }
            '<=' { return $cmp -le 0 }
            '>'  { return $cmp -gt 0 }
            '>=' { return $cmp -ge 0 }
            '='  { return $cmp -eq 0 }
        }
    }
    return $false
}

function Get-Sha256([string]$Path) {
    if (-not (Test-Path $Path)) { return $null }
    return (Get-FileHash $Path -Algorithm SHA256).Hash.ToLowerInvariant()
}

$php = Get-PhpPath
$phpVersion = (& $php -r 'echo PHP_VERSION;')
$policy = Get-Content (Join-Path $RepoPath 'policy\release-policy.json') -Raw | ConvertFrom-Json
$advisories = (Get-Content (Join-Path $RepoPath 'policy\advisories.json') -Raw | ConvertFrom-Json).advisories
$lock = Get-Content (Join-Path $RepoPath 'composer.lock') -Raw | ConvertFrom-Json
$dockerfile = Get-Content (Join-Path $RepoPath 'Dockerfile') -Raw
$revisionFile = Join-Path $RepoPath 'SOURCE_REVISION'
$revision = if (Test-Path $revisionFile) { (Get-Content $revisionFile -Raw).Trim() } else { 'unversioned' }

$stages = [ordered]@{}
$reasons = New-Object System.Collections.Generic.List[string]
$findings = New-Object System.Collections.Generic.List[object]

# A policy that disables blocking severities or allows blanket suppression is
# itself a release blocker.
if ($policy.allow_blanket_suppression -or @($policy.block_severities).Count -eq 0) {
    $reasons.Add('POLICY_WEAKENED')
}

# --- Stage 1: preflight ---------------------------------------------------
$required = @('app\public\index.php', 'app\src\store.php', 'composer.json', 'composer.lock', 'Dockerfile', 'policy\release-policy.json')
$missing = @($required | Where-Object { -not (Test-Path (Join-Path $RepoPath $_)) })
$stages.preflight = if ($missing.Count -eq 0) { 'pass' } else { 'fail' }
if ($missing.Count -gt 0) { $reasons.Add('PREFLIGHT_MISSING_INPUT') }
$stages.preflight_detail = [ordered]@{ php_version = $phpVersion; missing = $missing }

# --- Stage 2: build --------------------------------------------------------
$digest = (& $php (Join-Path $RepoPath 'tools\build.php') $RepoPath | Out-String).Trim()
$artifactPath = Join-Path $RepoPath 'build\artifact.json'
$stages.build = if (Test-Path $artifactPath) { 'pass' } else { 'fail' }
if (-not (Test-Path $artifactPath)) { $reasons.Add('BUILD_FAILED') }
$artifact = if (Test-Path $artifactPath) { Get-Content $artifactPath -Raw | ConvertFrom-Json } else { $null }
$lockfileSha = Get-Sha256 (Join-Path $RepoPath 'composer.lock')

# --- Stage 3: security ----------------------------------------------------
$blockSeverities = @($policy.block_severities)
$packages = @($lock.packages)
$dependencyFindings = @()
foreach ($package in $packages) {
    foreach ($advisory in $advisories) {
        if ($advisory.package -eq $package.name -and (Test-Affected $package.version $advisory.affected)) {
            $dependencyFindings += [pscustomobject]@{
                kind = 'dependency'
                id = $advisory.id
                package = $advisory.package
                version = $package.version
                severity = $advisory.severity
                summary = $advisory.summary
            }
        }
    }
}
foreach ($f in $dependencyFindings) {
    $findings.Add($f)
    if ($blockSeverities -contains $f.severity) { $reasons.Add('DEP_' + $f.id) }
}

# Secret scan recomputed from the artifact config, not trusted from the report.
$configPath = Join-Path $RepoPath 'app\config.php'
$secretHits = @()
if (Test-Path $configPath) {
    $configText = Get-Content $configPath -Raw
    if ($configText -match "(sk_(test|live)_[A-Za-z0-9_]+)") {
        $secretHits += $Matches[1].Substring(0, [Math]::Min(12, $Matches[1].Length)) + '...'
    }
}
if ($secretHits.Count -gt 0) {
    $findings.Add([pscustomobject]@{ kind = 'secret'; id = 'SECRET_IN_SOURCE'; severity = 'high'; summary = 'A credential-shaped string is present in shipped source.' })
    $reasons.Add('SECRET_IN_SOURCE')
}

# Container user check.
$userLines = @([regex]::Matches($dockerfile, '(?im)^\s*USER\s+(\S+)\s*$') | ForEach-Object { $_.Groups[1].Value })
$runtimeUser = if ($userLines.Count -gt 0) { $userLines[-1] } else { '(none)' }
if ($policy.require_non_root_container -and ($runtimeUser -eq '(none)' -or $runtimeUser -eq 'root' -or $runtimeUser -eq '0')) {
    $findings.Add([pscustomobject]@{ kind = 'container'; id = 'CONTAINER_ROOT_USER'; severity = 'high'; summary = "Runtime user is '$runtimeUser'." })
    $reasons.Add('CONTAINER_ROOT_USER')
}

$stages.security = if (@($reasons | Where-Object { $_ -match '^(DEP_|SECRET_|CONTAINER_)' }).Count -eq 0) { 'pass' } else { 'fail' }

# --- Stage 4: SBOM --------------------------------------------------------
$sbomPath = Join-Path $RepoPath 'sbom\campusapi.cdx.json'
$sbomSha = Get-Sha256 $sbomPath
$sbomSubject = $null
$sbomPackages = @()
if (Test-Path $sbomPath) {
    $sbom = Get-Content $sbomPath -Raw | ConvertFrom-Json
    $hashes = @($sbom.metadata.component.hashes)
    if ($hashes.Count -gt 0) { $sbomSubject = $hashes[0].content }
    $sbomPackages = @($sbom.components | ForEach-Object { "$($_.name)@$($_.version)" })
}
$lockPackages = @($packages | ForEach-Object { "$($_.name)@$($_.version)" })
$sbomProblems = @()
if ($policy.require_sbom -and -not (Test-Path $sbomPath)) { $sbomProblems += 'SBOM_MISSING' }
if ($sbomSubject -and $artifact -and $sbomSubject -ne $artifact.artifact_digest) { $sbomProblems += 'SBOM_SUBJECT_MISMATCH' }
if ($sbomPackages.Count -gt 0) {
    $diff = Compare-Object -ReferenceObject $lockPackages -DifferenceObject $sbomPackages
    if ($diff) { $sbomProblems += 'SBOM_PACKAGE_MISMATCH' }
}
foreach ($p in $sbomProblems) { $reasons.Add($p) }
$stages.sbom = if ($sbomProblems.Count -eq 0) { 'pass' } else { 'fail' }

# --- Stage 5: functional tests against the built artifact -----------------
$functional = 'skipped'
$serverProcess = $null
$previousData = $env:CAMPUS_DATA
try {
    if (Test-Path (Join-Path $RepoPath 'build\artifact\app\public\index.php')) {
        $dataFile = Join-Path $RepoPath 'build\runtime\enrollments.json'
        New-Item -ItemType Directory -Path (Split-Path -Parent $dataFile) -Force | Out-Null
        if (Test-Path $dataFile) { Remove-Item $dataFile -Force }
        $env:CAMPUS_DATA = $dataFile
        $serverProcess = Start-Process -FilePath $php -ArgumentList @(
            '-d', 'display_errors=0',
            '-S', "127.0.0.1:$Port",
            '-t', 'build/artifact/app/public',
            'build/artifact/app/public/router.php'
        ) -WorkingDirectory $RepoPath -WindowStyle Hidden -PassThru `
            -RedirectStandardOutput (Join-Path $RepoPath 'build\server.out.log') `
            -RedirectStandardError (Join-Path $RepoPath 'build\server.err.log')

        $deadline = (Get-Date).AddSeconds(15)
        $ready = $false
        while ((Get-Date) -lt $deadline) {
            try {
                $health = Invoke-RestMethod -Uri "http://127.0.0.1:$Port/healthz" -TimeoutSec 2
                if ($health.status -eq 'ok') { $ready = $true; break }
            } catch { Start-Sleep -Milliseconds 250 }
        }
        if ($ready) {
            & $php (Join-Path $RepoPath 'tools\functional.php') --base "http://127.0.0.1:$Port"
            $functional = if ($LASTEXITCODE -eq 0) { 'pass' } else { 'fail' }
        } else {
            $functional = 'fail'
        }
    } else {
        $functional = 'fail'
    }
} finally {
    if ($serverProcess -and -not $serverProcess.HasExited) { Stop-Process -Id $serverProcess.Id -Force }
    if ($null -eq $previousData) { Remove-Item Env:\CAMPUS_DATA -ErrorAction SilentlyContinue } else { $env:CAMPUS_DATA = $previousData }
}
if ($functional -ne 'pass' -and $policy.require_functional_tests) { $reasons.Add('FUNCTIONAL_TEST_FAILED') }
$stages.functional = $functional

# --- Stage 6: artifact binding -------------------------------------------
$artifactProblems = @()
if (-not $artifact) { $artifactProblems += 'ARTIFACT_MISSING' }
else {
    if ($artifact.lockfile_sha256 -ne $lockfileSha) { $artifactProblems += 'ARTIFACT_LOCKFILE_MISMATCH' }
    if ($artifact.source_revision -ne $revision) { $artifactProblems += 'ARTIFACT_SOURCE_MISMATCH' }
    $expectedDigest = (Get-Content (Join-Path $RepoPath 'build\artifact.sha256') -Raw).Trim()
    if ($artifact.artifact_digest -ne $expectedDigest) { $artifactProblems += 'ARTIFACT_DIGEST_MISMATCH' }
}
foreach ($p in $artifactProblems) { $reasons.Add($p) }
$stages.artifact = if ($artifactProblems.Count -eq 0) { 'pass' } else { 'fail' }

$blocking = @($reasons | Where-Object { $_ -notmatch '^(SBOM_|DEP_ACME-2026-000[23])' })
# Medium/low advisories are recorded but only block if policy lists their severity.
$decision = if ($reasons.Count -eq 0) { 'pass' } else { 'fail' }

$result = [ordered]@{
    gate = 'dev1-campusapi-release-gate'
    gate_version = $gateVersion
    tool_versions = [ordered]@{ php = $phpVersion; build = 'dev1-build-1.0.0'; sbom = 'dev1-make-sbom-1.0.0' }
    policy_version = $policy.policy_version
    source_revision = $revision
    lockfile_sha256 = $lockfileSha
    artifact_digest = if ($artifact) { $artifact.artifact_digest } else { $null }
    sbom_sha256 = $sbomSha
    sbom_subject_digest = $sbomSubject
    stages = $stages
    findings = $findings
    reasons = $reasons
    decision = $decision
}

$outPath = if ($Out) { $Out } else { Join-Path $RepoPath 'build\gate-result.json' }
New-Item -ItemType Directory -Path (Split-Path -Parent $outPath) -Force | Out-Null
$result | ConvertTo-Json -Depth 8 | Set-Content -Path $outPath -Encoding UTF8

Write-Output "decision: $decision"
Write-Output "source:   $revision"
Write-Output "lockfile: $lockfileSha"
Write-Output "artifact: $($result.artifact_digest)"
Write-Output "sbom:     $sbomSubject"
if ($reasons.Count -gt 0) { Write-Output ("reasons:  " + ($reasons -join ', ')) }
Write-Output "result:   $outPath"

if ($Freeze) {
    $freeze = [ordered]@{
        frozen_at_utc = (Get-Date).ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ')
        gate_sha256 = Get-Sha256 (Join-Path $RepoPath 'tools\gate.ps1')
        policy_sha256 = Get-Sha256 (Join-Path $RepoPath 'policy\release-policy.json')
        advisories_sha256 = Get-Sha256 (Join-Path $RepoPath 'policy\advisories.json')
        lockfile_sha256 = $lockfileSha
        artifact_digest = $result.artifact_digest
        sbom_sha256 = $sbomSha
        decision = $decision
    }
    $freezePath = Join-Path $RepoPath 'build\freeze.json'
    $freeze | ConvertTo-Json -Depth 5 | Set-Content -Path $freezePath -Encoding UTF8
    Write-Output "freeze:   $freezePath"
}

if ($decision -ne 'pass') { exit 1 }
exit 0
