<?php

use StorePackage\WarehousePdoAdapter\Config\PdoAdapterConfig;

return new PdoAdapterConfig(
    'mysql:host=127.0.0.1;port=3306;dbname=warehouse',
    'warehouse',
    'warehouse',
    array(),
    array(),
    15,
    0,
    0,
    array(
        "SET NAMES utf8mb4",
    )
);
