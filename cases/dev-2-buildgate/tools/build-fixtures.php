<?php
declare(strict_types=1);

/**
 * Build inert DEV-2 fixtures: one deterministic archive plus attestation
 * variants. The archive is never executed. Golden output stays instructor-only.
 *
 * Usage: php tools/build-fixtures.php
 */

$caseRoot = dirname(__DIR__);
$repoRoot = dirname($caseRoot, 2);
$lib = $caseRoot . '/lib';
$publicFixtures = $caseRoot . '/fixtures';
$goldenDir = $repoRoot . '/instructor-private/dev-2/golden';

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

function build_zip(string $lib, string $target, string $extraComment = ''): string
{
    $files = [
        'composer.json' => file_get_contents($lib . '/composer.json'),
        'composer.lock' => file_get_contents($lib . '/composer.lock'),
        'src/TextKit.php' => file_get_contents($lib . '/src/TextKit.php') . $extraComment,
    ];
    ksort($files);
    if (is_file($target)) {
        unlink($target);
    }
    $zip = new ZipArchive();
    if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('cannot create ' . $target);
    }
    $mtime = strtotime('2026-11-01T00:00:00Z') ?: 0;
    foreach ($files as $name => $content) {
        $zip->addFromString($name, $content);
        if (method_exists($zip, 'setMtimeName')) {
            $zip->setMtimeName($name, $mtime);
        }
    }
    $zip->close();
    return hash_file('sha256', $target);
}

function attestation(string $artifactDigest, string $revision, string $lockSha, string $builder, string $finishedOn): array
{
    return [
        '_type' => 'https://in-toto.io/Statement/v1',
        'subject' => [
            ['name' => 'library-1.0.0.zip', 'digest' => ['sha256' => $artifactDigest]],
        ],
        'predicateType' => 'https://slsa.dev/provenance/v1',
        'predicate' => [
            'buildDefinition' => [
                'buildType' => 'https://example.test/buildtypes/local-fixture/v1',
                'externalParameters' => [
                    'source' => ['uri' => 'file:///synthetic/textkit', 'revision' => $revision],
                    'lockfile_sha256' => $lockSha,
                ],
                'resolvedDependencies' => [
                    ['uri' => 'pkg:composer/acme/text-common@1.4.2', 'digest' => ['sha256' => $lockSha]],
                ],
            ],
            'runDetails' => [
                'builder' => ['id' => $builder],
                'metadata' => [
                    'invocationId' => 'inv-' . substr($artifactDigest, 0, 12),
                    'startedOn' => $finishedOn,
                    'finishedOn' => $finishedOn,
                ],
            ],
        ],
    ];
}

function write_fixture(string $dir, string $artifactSource, array $att, string $artifactName = 'library-1.0.0.zip'): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $artifact = $dir . '/' . $artifactName;
    copy($artifactSource, $artifact);
    $attPath = $dir . '/attestation.json';
    file_put_contents($attPath, json_encode($att, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    $lines = [
        hash_file('sha256', $artifact) . '  ' . $artifactName,
        hash_file('sha256', $attPath) . '  attestation.json',
    ];
    file_put_contents($dir . '/sha256.txt', implode("\n", $lines) . "\n");
}

$revision = trim((string) file_get_contents($lib . '/SOURCE_REVISION'));
$lockSha = hash_file('sha256', $lib . '/composer.lock');

rrmdir($publicFixtures);
mkdir($publicFixtures, 0777, true);
if (!is_dir($goldenDir)) {
    mkdir($goldenDir, 0777, true);
}

$goldenZip = $goldenDir . '/library-1.0.0.zip';
$goldenDigest = build_zip($lib, $goldenZip);
$valid = attestation($goldenDigest, $revision, $lockSha, 'builder://lab-buildgate-1', '2026-11-08T10:00:00Z');
file_put_contents($goldenDir . '/attestation.json', json_encode($valid, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
file_put_contents($goldenDir . '/sha256.txt', implode("\n", [
    $goldenDigest . '  library-1.0.0.zip',
    hash_file('sha256', $goldenDir . '/attestation.json') . '  attestation.json',
]) . "\n");

// Tampered archive: self-consistent manifest, but the attestation names the golden digest.
$tamperedDir = $publicFixtures . '/tampered';
mkdir($tamperedDir, 0777, true);
$tamperedZip = $tamperedDir . '/library-1.0.0.zip';
build_zip($lib, $tamperedZip, "\n// inert tamper marker for the lab\n");
write_fixture($tamperedDir, $tamperedZip, $valid);

// Wrong source revision.
write_fixture($publicFixtures . '/wrong-source', $goldenZip,
    attestation($goldenDigest, 'buildgate-lib-2026.10-evil', $lockSha, 'builder://lab-buildgate-1', '2026-11-08T10:00:00Z'));

// Stale attestation.
write_fixture($publicFixtures . '/stale', $goldenZip,
    attestation($goldenDigest, $revision, $lockSha, 'builder://lab-buildgate-1', '2025-01-01T00:00:00Z'));

// Untrusted builder.
write_fixture($publicFixtures . '/untrusted-builder', $goldenZip,
    attestation($goldenDigest, $revision, $lockSha, 'builder://unknown-lab-2', '2026-11-08T10:00:00Z'));

// Missing builder identity.
write_fixture($publicFixtures . '/missing-builder', $goldenZip,
    attestation($goldenDigest, $revision, $lockSha, '', '2026-11-08T10:00:00Z'));

printf("Golden digest: %s\n", $goldenDigest);
printf("Public fixtures: %s\n", implode(', ', array_map('basename', glob($publicFixtures . '/*', GLOB_ONLYDIR) ?: [])));
printf("Golden (instructor only): %s\n", $goldenDir);
