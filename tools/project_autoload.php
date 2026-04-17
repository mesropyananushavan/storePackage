<?php

$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
    return;
}

spl_autoload_register(function ($class) {
    $prefixMap = array(
        'StorePackage\\WarehouseCore\\' => __DIR__ . '/../src/',
        'StorePackage\\WarehouseCore\\Tests\\' => __DIR__ . '/../tests/',
    );

    foreach ($prefixMap as $prefix => $baseDir) {
        if (strpos($class, $prefix) !== 0) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
