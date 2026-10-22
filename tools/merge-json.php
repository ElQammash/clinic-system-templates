<?php

/*
 * Merges translation JSON files for tools/sync-backend.ps1.
 * Usage: php tools/merge-json.php <source.json> <target.json> [--dry-run]
 *
 * Keys from the source are added or updated in the target; keys that only the target has
 * (template-specific strings) are kept. Output is sorted case-insensitively, UTF-8 without a BOM.
 * Prints the number of keys added or updated.
 */

[$script, $sourceFile, $targetFile] = array_pad($argv, 3, null);
$dryRun = in_array('--dry-run', $argv, true);

if (! $sourceFile || ! $targetFile || ! is_file($sourceFile)) {
    fwrite(STDERR, "usage: php merge-json.php <source.json> <target.json> [--dry-run]\n");
    exit(1);
}

$read = function (string $file): array {
    if (! is_file($file)) {
        return [];
    }
    $data = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($file)), true);
    if (! is_array($data)) {
        fwrite(STDERR, "invalid JSON in {$file}: ".json_last_error_msg()."\n");
        exit(1);
    }

    return $data;
};

$source = $read($sourceFile);
$target = $read($targetFile);

$changed = 0;
foreach ($source as $key => $value) {
    if (! array_key_exists($key, $target) || $target[$key] !== $value) {
        $changed++;
    }
    $target[$key] = $value;
}

if (! $dryRun && $changed > 0) {
    uksort($target, 'strcasecmp');
    file_put_contents($targetFile, json_encode($target, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
}

echo $changed;
