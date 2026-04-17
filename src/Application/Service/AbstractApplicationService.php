<?php

namespace StorePackage\WarehouseCore\Application\Service;

use StorePackage\WarehouseCore\Contracts\ClockInterface;
use StorePackage\WarehouseCore\Contracts\EventDispatcherInterface;
use StorePackage\WarehouseCore\Contracts\IdGeneratorInterface;
use StorePackage\WarehouseCore\Contracts\LoggerInterface;
use StorePackage\WarehouseCore\Contracts\TransactionManagerInterface;
use StorePackage\WarehouseCore\Domain\Exception\InvalidQuantityException;

abstract class AbstractApplicationService
{
    protected $transactions;
    protected $clock;
    protected $ids;
    protected $events;
    protected $logger;

    public function __construct(
        TransactionManagerInterface $transactions,
        ClockInterface $clock,
        IdGeneratorInterface $ids,
        EventDispatcherInterface $events,
        LoggerInterface $logger
    ) {
        $this->transactions = $transactions;
        $this->clock = $clock;
        $this->ids = $ids;
        $this->events = $events;
        $this->logger = $logger;
    }

    protected function requirePositiveQuantity($quantity, $message)
    {
        if ($quantity <= 0) {
            throw new InvalidQuantityException($message);
        }
    }

    protected function sumLotQuantity(array $lots)
    {
        $quantity = 0.0;
        foreach ($lots as $lot) {
            $quantity = $quantity + $lot->getQuantityRemaining();
        }

        return $quantity;
    }

    protected function resolveTimestamp($value)
    {
        return $value === null ? $this->clock->now() : $value;
    }

    protected function resolveOperationId($providedValue, $prefix)
    {
        if ($providedValue !== null && $providedValue !== '') {
            return $providedValue;
        }

        return $this->ids->generate($prefix);
    }

    protected function detectCurrencyFromLots(array $lots, $fallback)
    {
        if (!empty($lots)) {
            return $lots[0]->getCurrency();
        }

        return $fallback;
    }

    protected function extractLotIds(array $lots)
    {
        $ids = array();
        foreach ($lots as $lot) {
            $ids[] = $lot->getLotId();
        }

        return $ids;
    }
}
