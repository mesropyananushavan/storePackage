<?php

namespace StorePackage\WarehouseCore\Contracts;

interface LoggerInterface
{
    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context);

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context);
}
