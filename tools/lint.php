<?php

$directories = array(
    __DIR__ . '/../src',
    __DIR__ . '/../tests',
    __DIR__ . '/../examples',
    __DIR__ . '/../tools',
);

$files = array();

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        if (substr($fileInfo->getFilename(), -4) !== '.php') {
            continue;
        }

        $files[] = $fileInfo->getPathname();
    }
}

sort($files);

$hasErrors = false;
foreach ($files as $file) {
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1';
    exec($command, $output, $exitCode);
    fwrite(STDOUT, implode(PHP_EOL, $output) . PHP_EOL);
    if ($exitCode !== 0) {
        $hasErrors = true;
    }
    $output = array();
}

exit($hasErrors ? 1 : 0);
