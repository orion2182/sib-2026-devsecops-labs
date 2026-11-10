<?php
declare(strict_types=1);

/**
 * BuildGate synthetic library. Inert source; the packaged archive is never
 * executed by any lab step.
 */

function text_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function text_truncate(string $value, int $limit): string
{
    if ($limit < 1 || mb_strlen($value) <= $limit) {
        return $value;
    }
    return mb_substr($value, 0, $limit - 1) . '…';
}

function text_word_count(string $value): int
{
    $parts = preg_split('/\s+/', trim($value)) ?: [];
    return count(array_filter($parts, static fn (string $p): bool => $p !== ''));
}
