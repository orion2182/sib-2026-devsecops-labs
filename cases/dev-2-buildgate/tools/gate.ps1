#requires -Version 5.1
<#
.SYNOPSIS
BuildGate provenance gate (DEV-2).

.DESCRIPTION
Verifies an inert artifact against a SLSA-shaped attestation, pinned source
revision, lockfile digest, and builder trust policy. A file hash alone is not
treated as proof of origin. No archive is executed.

.EXAMPLE
.\tools\gate.ps1 -FixtureDir .\fixtures\tampered
.\tools\gate.ps1 -FixtureDir ..\..\instructor-private\dev-2\golden
#>
param(
    [Parameter(Mandatory = $true)]
    [string]$FixtureDir,
    [string]$CaseRoot = (Split-Path -Parent $PSScriptRoot),
    [string]$ReferenceDate = '2026-11-10'
)

$ErrorActionPreference = 'Stop'

function Get-PhpPath {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    foreach ($candidate in @('C:\xampp\php\php.exe', "$env:ProgramFiles\php\php.exe", "${env:ProgramFiles(x86)}\php\php.exe")) {
        if ($candidate -and (Test-Path $candidate)) { return $candidate }
    }
    throw 'PHP 8.1+ not found.'
}

$FixtureDir = (Resolve-Path $FixtureDir).Path
$CaseRoot = (Resolve-Path $CaseRoot).Path
$policy = Get-Content (Join-Path $CaseRoot 'policy\trust-policy.json') -Raw | ConvertFrom-Json
$revision = (Get-Content (Join-Path $CaseRoot 'lib\SOURCE_REVISION') -Raw).Trim()
$lockSha = (Get-FileHash (Join-Path $CaseRoot 'lib\composer.lock') -Algorithm SHA256).Hash.ToLowerInvariant()

$reasons = New-Object System.Collections.Generic.List[string]
$checks = [ordered]@{}

$artifactName = $policy.required_subject_name
$artifactPath = Join-Path $FixtureDir $artifactName
$attestationPath = Join-Path $FixtureDir 'attestation.json'
$manifestPath = Join-Path $FixtureDir 'sha256.txt'

$artifactDigest = $null
if (-not (Test-Path $artifactPath)) {
    $reasons.Add('ARTIFACT_MISSING')
} else {
    $artifactDigest = (Get-FileHash $artifactPath -Algorithm SHA256).Hash.ToLowerInvariant()
}
$checks.artifact_digest = $artifactDigest

# Manifest consistency (self-consistent hashes are necessary, not sufficient).
if (-not (Test-Path $manifestPath)) {
    $reasons.Add('MANIFEST_MISSING')
} else {
    $manifest = Get-Content $manifestPath -Raw
    if ($artifactDigest -and $manifest -notmatch [regex]::Escape($artifactDigest)) {
        $reasons.Add('MANIFEST_MISMATCH')
    }
}

$att = $null
if (-not (Test-Path $attestationPath)) {
    $reasons.Add('ATTESTATION_MISSING')
} else {
    $att = Get-Content $attestationPath -Raw | ConvertFrom-Json
}

$subjectDigest = $null
$subjectName = $null
$builderId = $null
$attRevision = $null
$attLockSha = $null
$finishedOn = $null
$signatureVerified = $null
$keyFingerprint = $null
$publicKeyPath = Join-Path $FixtureDir 'public.pem'
if (-not (Test-Path $publicKeyPath)) {
    $publicKeyPath = Join-Path $CaseRoot 'policy\buildgate-public.pem'
}

if ($policy.require_signature) {
    $sigPath = Join-Path $FixtureDir 'attestation.sig'
    if (-not (Test-Path $sigPath)) {
        $reasons.Add('MISSING_SIGNATURE')
    } elseif (-not $att) {
        $reasons.Add('SIGNATURE_WITHOUT_ATTESTATION')
    } elseif (-not (Test-Path $publicKeyPath)) {
        $reasons.Add('PUBLIC_KEY_MISSING')
    } else {
        $php = Get-PhpPath
        $verifier = Join-Path $CaseRoot 'tools\verify-signature.php'
        $raw = & $php $verifier $attestationPath $sigPath $publicKeyPath $policy.trusted_key_sha256 2>$null
        $parsed = $raw | ConvertFrom-Json
        $signatureVerified = [bool]$parsed.ok
        $keyFingerprint = $parsed.fingerprint
        if (-not $parsed.ok) {
            $reason = [string]$parsed.reason
            if ([string]::IsNullOrWhiteSpace($reason)) { $reason = 'BAD_SIGNATURE' }
            $reasons.Add($reason)
        }
    }
}

if ($att) {
    $subject = @($att.subject)[0]
    $subjectName = $subject.name
    $subjectDigest = $subject.digest.sha256
    $builderId = $att.predicate.runDetails.builder.id
    $attRevision = $att.predicate.buildDefinition.externalParameters.source.revision
    $attLockSha = $att.predicate.buildDefinition.externalParameters.lockfile_sha256
    $finishedOn = $att.predicate.runDetails.metadata.finishedOn

    if ($subjectName -ne $policy.required_subject_name) { $reasons.Add('SUBJECT_NAME_MISMATCH') }
    if (-not $artifactDigest -or $subjectDigest -ne $artifactDigest) { $reasons.Add('SUBJECT_DIGEST_MISMATCH') }
    if ([string]::IsNullOrWhiteSpace($builderId)) {
        $reasons.Add('MISSING_BUILDER')
    } elseif (@($policy.trusted_builders) -notcontains $builderId) {
        $reasons.Add('UNTRUSTED_BUILDER')
    }
    if ($policy.require_source_revision -and $attRevision -ne $revision) { $reasons.Add('WRONG_SOURCE') }
    if ($policy.require_lockfile_match -and $attLockSha -ne $lockSha) { $reasons.Add('LOCKFILE_MISMATCH') }
    if ($finishedOn) {
        try {
            $finished = [DateTimeOffset]::Parse($finishedOn)
            $reference = [DateTimeOffset]::Parse($ReferenceDate + 'T00:00:00Z')
            $ageDays = ($reference - $finished).TotalDays
            if ($ageDays -gt [double]$policy.max_attestation_age_days) { $reasons.Add('STALE_ATTESTATION') }
            if ($ageDays -lt -1) { $reasons.Add('ATTESTATION_FROM_FUTURE') }
        } catch {
            $reasons.Add('ATTESTATION_TIME_INVALID')
        }
    }
}

$checks.subject_name = $subjectName
$checks.subject_digest = $subjectDigest
$checks.builder = $builderId
$checks.attested_revision = $attRevision
$checks.expected_revision = $revision
$checks.lockfile_sha256 = $lockSha
$checks.finished_on = $finishedOn
$checks.reference_date = $ReferenceDate
$checks.signature_verified = $signatureVerified
$checks.key_fingerprint = $keyFingerprint
$checks.trusted_key_fingerprint = $policy.trusted_key_sha256

$decision = if ($reasons.Count -eq 0) { 'pass' } else { 'fail' }
$result = [ordered]@{
    gate = 'dev2-buildgate-provenance'
    gate_version = '1.0.0'
    policy_version = $policy.policy_version
    fixture = (Split-Path -Leaf $FixtureDir)
    decision = $decision
    reasons = $reasons
    checks = $checks
    notes = 'Digest equality alone is not proof of trusted origin. Source, lockfile, builder, and freshness are all required.'
}

$out = Join-Path $FixtureDir 'gate-result.json'
$result | ConvertTo-Json -Depth 8 | Set-Content -Path $out -Encoding UTF8

Write-Output "decision: $decision"
if ($artifactDigest) { Write-Output "artifact: $artifactDigest" }
if ($subjectDigest) { Write-Output "subject:  $subjectDigest" }
if ($builderId) { Write-Output "builder:  $builderId" }
if ($reasons.Count -gt 0) { Write-Output ("reasons:  " + ($reasons -join ', ')) }
Write-Output "result:   $out"

if ($decision -ne 'pass') { exit 1 }
exit 0
