<?php
declare(strict_types=1);

/**
 * Generate a CycloneDX 1.5 SBOM from the lockfile and bind it to the exact
 * built artifact digest recorded in build/artifact.json.
 *
 * Usage: php tools/make-sbom.php <repo-root>
 */

$repo = rtrim($argv[1] ?? dirname(__DIR__), '/\\');
$artifactPath = $repo . '/build/artifact.json';
if (!is_file($artifactPath)) {
    fwrite(STDERR, "build/artifact.json missing; run tools/build.php first\n");
    exit(2);
}
$artifact = json_decode((string) file_get_contents($artifactPath), true);
$lock = json_decode((string) file_get_contents($repo . '/composer.lock'), true);
$digest = $artifact['artifact_digest'] ?? '';

$components = [];
$depends = [];
foreach (($lock['packages'] ?? []) as $package) {
    $version = (string) $package['version'];
    $ref = 'pkg:composer/' . $package['name'] . '@' . $version;
    $components[] = [
        'type' => 'library',
        'bom-ref' => $ref,
        'name' => $package['name'],
        'version' => $version,
        'purl' => $ref,
        'licenses' => array_map(static fn (string $l): array => ['license' => ['id' => $l]], $package['license'] ?? []),
    ];
    $depends[] = $ref;
}

$uuidBytes = substr(hash('sha256', $digest, true), 0, 16);
$uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($uuidBytes), 4));

$bom = [
    'bomFormat' => 'CycloneDX',
    'specVersion' => '1.5',
    'serialNumber' => 'urn:uuid:' . $uuid,
    'version' => 1,
    'metadata' => [
        'tools' => [['name' => 'dev1-make-sbom', 'version' => '1.0.0']],
        'component' => [
            'type' => 'application',
            'bom-ref' => 'campus/campusapi',
            'name' => 'campus/campusapi',
            'version' => $artifact['source_revision'] ?? 'unversioned',
            'hashes' => [
                ['alg' => 'SHA-256', 'content' => $digest],
            ],
        ],
    ],
    'components' => $components,
    'dependencies' => [
        ['ref' => 'campus/campusapi', 'dependsOn' => $depends],
    ],
];

$target = $repo . '/sbom/campusapi.cdx.json';
if (!is_dir(dirname($target))) {
    mkdir(dirname($target), 0777, true);
}
file_put_contents($target, json_encode($bom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "SBOM written: $target\n";
echo "subject digest: $digest\n";
