<?php
declare(strict_types=1);

/**
 * Verify a detached signature over a canonicalized attestation.
 * Usage: php tools/verify-signature.php <attestation.json> <attestation.sig> <public.pem> [expected_fingerprint]
 * Prints JSON and exits 0 when valid.
 */

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

function canonical_json(array $data): string
{
    return json_encode(canonical_value($data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function fingerprint(string $publicPem): string
{
    $normalized = trim($publicPem) . "\n";
    return hash('sha256', $normalized);
}

$attPath = $argv[1] ?? '';
$sigPath = $argv[2] ?? '';
$pubPath = $argv[3] ?? '';
$expected = $argv[4] ?? '';

if ($attPath === '' || $sigPath === '' || $pubPath === '') {
    fwrite(STDERR, "usage: verify-signature.php <att> <sig> <public.pem> [fingerprint]\n");
    exit(2);
}
if (!is_file($attPath) || !is_file($sigPath) || !is_file($pubPath)) {
    echo json_encode(['ok' => false, 'reason' => 'MISSING_SIGNATURE_MATERIAL']) . "\n";
    exit(1);
}

$att = json_decode((string) file_get_contents($attPath), true);
if (!is_array($att)) {
    echo json_encode(['ok' => false, 'reason' => 'ATTESTATION_INVALID']) . "\n";
    exit(1);
}
$publicPem = (string) file_get_contents($pubPath);
$actualFingerprint = fingerprint($publicPem);
if ($expected !== '' && !hash_equals($expected, $actualFingerprint)) {
    echo json_encode(['ok' => false, 'reason' => 'UNTRUSTED_KEY', 'fingerprint' => $actualFingerprint]) . "\n";
    exit(1);
}

$signature = base64_decode(trim((string) file_get_contents($sigPath)), true);
if ($signature === false) {
    echo json_encode(['ok' => false, 'reason' => 'BAD_SIGNATURE_ENCODING', 'fingerprint' => $actualFingerprint]) . "\n";
    exit(1);
}

$bytes = canonical_json($att);
$key = openssl_pkey_get_public($publicPem);
if ($key === false) {
    echo json_encode(['ok' => false, 'reason' => 'PUBLIC_KEY_INVALID']) . "\n";
    exit(1);
}
$verified = openssl_verify($bytes, $signature, $key, OPENSSL_ALGO_SHA256);
echo json_encode([
    'ok' => $verified === 1,
    'reason' => $verified === 1 ? null : 'BAD_SIGNATURE',
    'fingerprint' => $actualFingerprint,
]) . "\n";
exit($verified === 1 ? 0 : 1);
