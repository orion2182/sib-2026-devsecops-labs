<?php
declare(strict_types=1);

/**
 * Build inert DEV-2 fixtures with detached RSA signatures.
 * Private keys stay under instructor-private/. Public key is committed in policy/.
 *
 * Usage: php tools/build-fixtures.php
 */

$caseRoot = dirname(__DIR__);
$repoRoot = dirname($caseRoot, 2);
$lib = $caseRoot . '/lib';
$publicFixtures = $caseRoot . '/fixtures';
$goldenDir = $repoRoot . '/instructor-private/dev-2/golden';
$keysDir = $repoRoot . '/instructor-private/dev-2/keys';
$publicKeyPath = $caseRoot . '/policy/buildgate-public.pem';
$policyPath = $caseRoot . '/policy/trust-policy.json';

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

function canonical_value($value)
{
    if (is_array($value)) {
        if (array_is_list($value)) {
            return array_map('canonical_value', $value);
        }
        ksort($value);
        foreach ($value as $k => $v) {
            $value[$k] = canonical_value($v);
        }
    }
    return $value;
}

function canonical_bytes(array $data): string
{
    return json_encode(canonical_value($data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function fingerprint(string $publicPem): string
{
    return hash('sha256', trim($publicPem) . "\n");
}

function ensure_keypair(string $privatePath, string $publicPath): void
{
    if (is_file($privatePath) && is_file($publicPath)) {
        return;
    }
    $dir = dirname($privatePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
    foreach ([
        getenv('OPENSSL_CONF') ?: '',
        'C:/xampp/php/extras/openssl/openssl.cnf',
        'C:/xampp/apache/conf/openssl.cnf',
    ] as $config) {
        if ($config !== '' && is_file($config)) {
            $options['config'] = $config;
            break;
        }
    }
    $resource = openssl_pkey_new($options);
    if ($resource === false) {
        throw new RuntimeException('openssl_pkey_new failed: ' . openssl_error_string());
    }
    if (!openssl_pkey_export($resource, $privatePem, null, $options)) {
        throw new RuntimeException('openssl_pkey_export failed: ' . openssl_error_string());
    }
    $details = openssl_pkey_get_details($resource);
    file_put_contents($privatePath, $privatePem);
    file_put_contents($publicPath, $details['key']);
}

function sign_attestation(array $att, string $privatePem): string
{
    $key = openssl_pkey_get_private($privatePem);
    if ($key === false) {
        throw new RuntimeException('invalid private key');
    }
    $signature = '';
    if (!openssl_sign(canonical_bytes($att), $signature, $key, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('openssl_sign failed');
    }
    return base64_encode($signature);
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

function write_fixture(
    string $dir,
    string $artifactSource,
    array $att,
    ?string $signature,
    ?string $extraPublicPem = null
): void {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $artifactName = 'library-1.0.0.zip';
    $artifact = $dir . '/' . $artifactName;
    copy($artifactSource, $artifact);
    $attPath = $dir . '/attestation.json';
    file_put_contents($attPath, json_encode($att, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    $lines = [
        hash_file('sha256', $artifact) . '  ' . $artifactName,
        hash_file('sha256', $attPath) . '  attestation.json',
    ];
    if ($signature !== null) {
        $sigPath = $dir . '/attestation.sig';
        file_put_contents($sigPath, $signature . "\n");
        $lines[] = hash_file('sha256', $sigPath) . '  attestation.sig';
    }
    if ($extraPublicPem !== null) {
        $pubPath = $dir . '/public.pem';
        file_put_contents($pubPath, $extraPublicPem);
        $lines[] = hash_file('sha256', $pubPath) . '  public.pem';
    }
    file_put_contents($dir . '/sha256.txt', implode("\n", $lines) . "\n");
}

$revision = trim((string) file_get_contents($lib . '/SOURCE_REVISION'));
$lockSha = hash_file('sha256', $lib . '/composer.lock');

ensure_keypair($keysDir . '/trusted-private.pem', $publicKeyPath);
$trustedPrivate = (string) file_get_contents($keysDir . '/trusted-private.pem');
$trustedPublic = (string) file_get_contents($publicKeyPath);
$trustedFingerprint = fingerprint($trustedPublic);

// Alt key for the untrusted-key fixture.
ensure_keypair($keysDir . '/alt-private.pem', $keysDir . '/alt-public.pem');
$altPrivate = (string) file_get_contents($keysDir . '/alt-private.pem');
$altPublic = (string) file_get_contents($keysDir . '/alt-public.pem');

// Publish the trusted fingerprint in the policy.
$policy = json_decode((string) file_get_contents($policyPath), true);
$policy['require_signature'] = true;
$policy['trusted_key_sha256'] = $trustedFingerprint;
$policy['notes'] = 'A matching file hash alone is not proof of trusted origin. The gate verifies a detached RSA-SHA256 signature over a canonicalized attestation, then checks subject, source, lockfile, builder, and freshness.';
file_put_contents($policyPath, json_encode($policy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

rrmdir($publicFixtures);
mkdir($publicFixtures, 0777, true);
if (!is_dir($goldenDir)) {
    mkdir($goldenDir, 0777, true);
}

$goldenZip = $goldenDir . '/library-1.0.0.zip';
$goldenDigest = build_zip($lib, $goldenZip);
$valid = attestation($goldenDigest, $revision, $lockSha, 'builder://lab-buildgate-1', '2026-11-08T10:00:00Z');
$validSig = sign_attestation($valid, $trustedPrivate);
write_fixture($goldenDir, $goldenZip, $valid, $validSig);

// Tampered artifact with a valid signature over the golden subject.
$tamperedDir = $publicFixtures . '/tampered';
mkdir($tamperedDir, 0777, true);
$tamperedZip = $tamperedDir . '/library-1.0.0.zip';
build_zip($lib, $tamperedZip, "\n// inert tamper marker for the lab\n");
write_fixture($tamperedDir, $tamperedZip, $valid, $validSig);

$wrongSource = attestation($goldenDigest, 'buildgate-lib-2026.10-evil', $lockSha, 'builder://lab-buildgate-1', '2026-11-08T10:00:00Z');
write_fixture($publicFixtures . '/wrong-source', $goldenZip, $wrongSource, sign_attestation($wrongSource, $trustedPrivate));

$stale = attestation($goldenDigest, $revision, $lockSha, 'builder://lab-buildgate-1', '2025-01-01T00:00:00Z');
write_fixture($publicFixtures . '/stale', $goldenZip, $stale, sign_attestation($stale, $trustedPrivate));

$untrustedBuilder = attestation($goldenDigest, $revision, $lockSha, 'builder://unknown-lab-2', '2026-11-08T10:00:00Z');
write_fixture($publicFixtures . '/untrusted-builder', $goldenZip, $untrustedBuilder, sign_attestation($untrustedBuilder, $trustedPrivate));

$missingBuilder = attestation($goldenDigest, $revision, $lockSha, '', '2026-11-08T10:00:00Z');
write_fixture($publicFixtures . '/missing-builder', $goldenZip, $missingBuilder, sign_attestation($missingBuilder, $trustedPrivate));

// No signature at all.
write_fixture($publicFixtures . '/unsigned', $goldenZip, $valid, null);

// Corrupted signature.
write_fixture($publicFixtures . '/bad-signature', $goldenZip, $valid, base64_encode(random_bytes(256)));

// Valid signature from a different key, with that key shipped in the fixture.
$altSig = sign_attestation($valid, $altPrivate);
write_fixture($publicFixtures . '/untrusted-key', $goldenZip, $valid, $altSig, $altPublic);

printf("Golden digest: %s\n", $goldenDigest);
printf("Trusted key fingerprint: %s\n", $trustedFingerprint);
printf("Fixtures: %s\n", implode(', ', array_map('basename', glob($publicFixtures . '/*', GLOB_ONLYDIR) ?: [])));
