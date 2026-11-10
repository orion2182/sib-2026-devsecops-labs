<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/store.php';

function out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$config = require dirname(__DIR__) . '/config.php';

if ($path === '/healthz' && $method === 'GET') {
    out(['status' => 'ok', 'app' => $config['app_name'], 'build' => getenv('CAMPUS_BUILD') ?: 'local']);
}

if ($path === '/api/courses' && $method === 'GET') {
    out(['courses' => campus_courses()]);
}

if (preg_match('#^/api/students/([0-9]{4})$#', $path, $m) && $method === 'GET') {
    $students = campus_students();
    if (!isset($students[$m[1]])) {
        out(['error' => 'student_not_found'], 404);
    }
    out(['student' => $students[$m[1]]]);
}

if ($path === '/api/enroll' && $method === 'POST') {
    $raw = file_get_contents('php://input') ?: '';
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        out(['error' => 'invalid_json'], 400);
    }
    $result = campus_enroll((string) ($body['student_id'] ?? ''), (string) ($body['course_id'] ?? ''));
    if (!$result['ok']) {
        out(['error' => $result['reason']], $result['status']);
    }
    out(['enrollment' => $result['enrollment']], 201);
}

out(['error' => 'not_found', 'path' => $path], 404);
