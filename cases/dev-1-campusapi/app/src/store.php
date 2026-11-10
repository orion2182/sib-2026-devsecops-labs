<?php
declare(strict_types=1);

/**
 * CampusAPI synthetic fixture store. No database, no external calls.
 */

function campus_courses(): array
{
    return [
        ['id' => 'CRS-101', 'title' => 'Keamanan Aplikasi Web', 'credits' => 3, 'seats' => 40],
        ['id' => 'CRS-102', 'title' => 'Forensik Digital', 'credits' => 3, 'seats' => 30],
        ['id' => 'CRS-103', 'title' => 'Tata Kelola Risiko', 'credits' => 2, 'seats' => 50],
    ];
}

function campus_students(): array
{
    return [
        '1001' => ['id' => '1001', 'name' => 'Aurel Pradita', 'program' => 'S1 Informatika'],
        '1002' => ['id' => '1002', 'name' => 'Bima Nugraha', 'program' => 'S1 Sistem Informasi'],
    ];
}

/** Enrollments persist in a small JSON file so HTTP requests share state. */
function campus_data_file(): string
{
    $configured = getenv('CAMPUS_DATA');
    return $configured !== false && $configured !== ''
        ? $configured
        : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'campusapi-enrollments.json';
}

function campus_enrollments(): array
{
    $file = campus_data_file();
    if (!is_file($file)) {
        return [];
    }
    $rows = json_decode((string) file_get_contents($file), true);
    return is_array($rows) ? $rows : [];
}

function campus_save_enrollments(array $rows): void
{
    $file = campus_data_file();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n", LOCK_EX);
}

function campus_enroll(string $studentId, string $courseId): array
{
    $rows = campus_enrollments();
    $students = campus_students();
    $courses = [];
    foreach (campus_courses() as $course) {
        $courses[$course['id']] = $course;
    }
    if (!isset($students[$studentId])) {
        return ['ok' => false, 'status' => 404, 'reason' => 'student_not_found'];
    }
    if (!isset($courses[$courseId])) {
        return ['ok' => false, 'status' => 404, 'reason' => 'course_not_found'];
    }
    foreach ($rows as $row) {
        if ($row['student_id'] === $studentId && $row['course_id'] === $courseId) {
            return ['ok' => false, 'status' => 409, 'reason' => 'already_enrolled'];
        }
    }
    $row = [
        'id' => 'ENR-' . (1000 + count($rows) + 1),
        'student_id' => $studentId,
        'course_id' => $courseId,
        'status' => 'enrolled',
    ];
    $rows[] = $row;
    campus_save_enrollments($rows);
    return ['ok' => true, 'status' => 201, 'enrollment' => $row];
}
