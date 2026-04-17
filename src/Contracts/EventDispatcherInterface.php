<?php

namespace StorePackage\WarehouseCore\Contracts;

interface EventDispatcherInterface
{
    /**
     * @param string $eventName
     * @param array $payload
     * @return void
     */
    public function dispatch($eventName, array $payload);
}
