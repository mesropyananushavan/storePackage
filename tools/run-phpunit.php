<?php

$configFile = __DIR__ . '/../phpunit.xml.dist';
$vendorBinary = __DIR__ . '/../vendor/bin/phpunit';
$binary = getenv('PHPUNIT_BINARY');

if ($binary === false || $binary === '') {
    if (file_exists($vendorBinary)) {
        $binary = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($vendorBinary);
    } else {
        fwrite(STDERR, "PHPUnit binary was not found.\n");
        fwrite(STDERR, "Install dev dependencies in a PHP environment with ext-dom and ext-xmlwriter, or set PHPUNIT_BINARY to a phpunit PHAR/binary.\n");
        fwrite(STDERR, "Fallback checks are available through `composer verify` and `composer test:smoke`.\n");
        exit(1);
    }
} else {
    $binary = $binary;
}

$command = $binary . ' --configuration ' . escapeshellarg($configFile);
passthru($command, $exitCode);
exit($exitCode);
