<?php

namespace StorePackage\WarehouseCore\Infrastructure\Stub;

use BadMethodCallException;

class HttpWarehouseApiStub
{
    public function pushMovement($movement)
    {
        throw new BadMethodCallException('Replace this stub with an HTTP/API integration adapter.');
    }
}
