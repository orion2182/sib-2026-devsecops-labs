<?php
declare(strict_types=1);

/**
 * Deterministic artifact build for DEV-1.
 *
 * The artifact digest is the SHA-256 of a canonical JSON document listing every
 * shipped file hash, the lockfile hash, the Dockerfile hash, and the recorded
 * source revision. No timestamps enter the digest, so repeated builds match.
 *
 * Usage: php tools/build.php <repo-root>
 */

$repo = rtrim($argv[1] ?? dirname(__DIR__), '/\\');
$buildDir = $repo . '/build';
$artifactDir = $buildDir . '/artifact';

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

function copytree(string $from, string $to): void
{
    if (!is_dir($to)) {
        mkdir($to, 0777, true);
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $rel = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($from))), '/');
        $target = $to . '/' . $rel;
        if ($item->isDir()) {
            if (!is_dir($target)) {
                mkdir($target, 0777, true);
            }
        } else {
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }
            copy($item->getPathname(), $target);
        }
    }
}

rrmdir($buildDir);
mkdir($artifactDir, 0777, true);
copytree($repo . '/app', $artifactDir . '/app');

$files = [];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($artifactDir, FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $rel = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($artifactDir))), '/');
    $files[$rel] = hash_file('sha256', $file->getPathname());
}
ksort($files);
$fileList = [];
foreach ($files as $path => $hash) {
    $fileList[] = ['path' => $path, 'sha256' => $hash];
}

$revisionFile = $repo . '/SOURCE_REVISION';
$revision = is_file($revisionFile) ? trim((string) file_get_contents($revisionFile)) : 'unversioned';

$canonical = [
    'schema' => 'dev1-artifact-1.0.0',
    'source_revision' => $revision,
    'lockfile_sha256' => hash_file('sha256', $repo . '/composer.lock'),
    'dockerfile_sha256' => hash_file('sha256', $repo . '/Dockerfile'),
    'files' => $fileList,
];
$digest = hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES));

$manifest = $canonical + [
    'artifact_digest' => $digest,
    'artifact_kind' => 'directory-manifest',
    'tool' => 'dev1-build-1.0.0',
];
file_put_contents($buildDir . '/artifact.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
file_put_contents($buildDir . '/artifact.sha256', $digest . "\n");

echo $digest . "\n";
