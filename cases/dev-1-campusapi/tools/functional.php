<?php
declare(strict_types=1);

/**
 * Functional checks against the exact built artifact over HTTP.
 * Usage: php tools/functional.php --base http://127.0.0.1:8096
 */

$options = getopt('', ['base:']);
$base = rtrim($options['base'] ?? 'http://127.0.0.1:8096', '/');
$failed = 0;

function req(string $method, string $path, ?array $body = null): array
{
    global $base;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => (string) $raw, 'json' => json_decode((string) $raw, true)];
}

function check(string $name, bool $ok, string $detail = ''): void
{
    global $failed;
    echo ($ok ? 'PASS  ' : 'FAIL  ') . $name . ($detail !== '' ? " — $detail" : '') . "\n";
    if (!$ok) {
        $failed++;
    }
}

$health = req('GET', '/healthz');
check('health endpoint', $health['status'] === 200 && ($health['json']['status'] ?? '') === 'ok');

$courses = req('GET', '/api/courses');
check('course list', $courses['status'] === 200 && count($courses['json']['courses'] ?? []) >= 3);

$student = req('GET', '/api/students/1001');
check('student lookup', $student['status'] === 200 && ($student['json']['student']['id'] ?? '') === '1001');

$enroll = req('POST', '/api/enroll', ['student_id' => '1001', 'course_id' => 'CRS-101']);
check('enrollment succeeds', $enroll['status'] === 201 && isset($enroll['json']['enrollment']['id']));

$duplicate = req('POST', '/api/enroll', ['student_id' => '1001', 'course_id' => 'CRS-101']);
check('duplicate enrollment rejected', $duplicate['status'] === 409);

$missing = req('GET', '/api/students/9999');
check('unknown student returns 404', $missing['status'] === 404);

if ($failed > 0) {
    fwrite(STDERR, "$failed functional check(s) failed\n");
    exit(1);
}
echo "Functional checks passed.\n";
exit(0);
