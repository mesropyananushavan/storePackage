<?php

namespace StorePackage\WarehouseCore\Infrastructure\Support;

use StorePackage\WarehouseCore\Contracts\EventDispatcherInterface;

class InMemoryEventDispatcher implements EventDispatcherInterface
{
    private $events;

    public function __construct()
    {
        $this->events = array();
    }

    public function dispatch($eventName, array $payload)
    {
        $this->events[] = array(
            'event' => $eventName,
            'payload' => $payload,
        );
    }

    public function all()
    {
        return $this->events;
    }
}
