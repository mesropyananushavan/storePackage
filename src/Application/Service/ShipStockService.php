<?php

namespace StorePackage\WarehouseCore\Application\Service;

use StorePackage\WarehouseCore\Application\Config\ValuationMethodResolver;
use StorePackage\WarehouseCore\Contracts\InventoryLotRepositoryInterface;
use StorePackage\WarehouseCore\Contracts\InventoryValuationSnapshotRepositoryInterface;
use StorePackage\WarehouseCore\Contracts\ReservationRepositoryInterface;
use StorePackage\WarehouseCore\Contracts\StockMovementRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\InventoryValuationSnapshot;
use StorePackage\WarehouseCore\Domain\Entity\Shipment;
use StorePackage\WarehouseCore\Domain\Entity\StockMovement;
use StorePackage\WarehouseCore\Domain\Exception\InsufficientStockException;
use StorePackage\WarehouseCore\Domain\Exception\InventoryLotNotFoundException;
use StorePackage\WarehouseCore\Domain\ValueObject\MovementType;
use StorePackage\WarehouseCore\Domain\ValueObject\ValuationMethod;

class ShipStockService extends AbstractApplicationService
{
    private $lots;
    private $movements;
    private $reservations;
    private $snapshots;
    private $resolver;

    public function __construct(
        InventoryLotRepositoryInterface $lots,
        StockMovementRepositoryInterface $movements,
        ReservationRepositoryInterface $reservations,
        InventoryValuationSnapshotRepositoryInterface $snapshots,
        ValuationMethodResolver $resolver,
        $transactions,
        $clock,
        $ids,
        $events,
        $logger
    ) {
        parent::__construct($transactions, $clock, $ids, $events, $logger);
        $this->lots = $lots;
        $this->movements = $movements;
        $this->reservations = $reservations;
        $this->snapshots = $snapshots;
        $this->resolver = $resolver;
    }

    public function ship(Shipment $shipment)
    {
        $this->requirePositiveQuantity($shipment->getQuantity(), 'Shipment quantity must be greater than zero.');
        $self = $this;

        return $this->transactions->transactional(function () use ($shipment, $self) {
            $operationId = $self->resolveOperationId($shipment->getShipmentId(), 'shipment');
            $strategy = $self->resolver->resolveStrategy(
                $shipment->getSku(),
                $shipment->getWarehouseId(),
                $shipment->getValuationMethod()
            );

            $lots = $strategy->getLotsForValuation(
                $self->lots,
                $shipment->getSku(),
                $shipment->getWarehouseId(),
                $shipment->getLocationId()
            );

            $onHand = $self->sumLotQuantity($lots);
            $reserved = $self->reservations->getReservedQuantity(
                $shipment->getSku(),
                $shipment->getWarehouseId(),
                $shipment->getLocationId()
            );
            $available = $onHand - $reserved;

            if ($shipment->getQuantity() > $available) {
                throw new InsufficientStockException($shipment->getSku(), $shipment->getQuantity(), $available);
            }

            $self->lots->lockLotsForUpdate($self->extractLotIds($lots));

            $result = $strategy->valuate(
                $lots,
                $shipment->getQuantity(),
                array(
                    'sku' => $shipment->getSku(),
                    'warehouse_id' => $shipment->getWarehouseId(),
                    'location_id' => $shipment->getLocationId(),
                )
            );

            foreach ($result->getAllocations() as $allocation) {
                $lot = $self->lots->findById($allocation->getLotId());
                if ($lot === null) {
                    throw new InventoryLotNotFoundException($allocation->getLotId());
                }

                $lot->decreaseRemaining($allocation->getQuantity());
                $self->lots->saveInventoryLot($lot);
            }

            $timestamp = $self->resolveTimestamp($shipment->getShippedAt());
            $movement = new StockMovement(
                $self->ids->generate('movement'),
                $operationId,
                MovementType::SHIPMENT,
                $shipment->getSku(),
                $shipment->getWarehouseId(),
                $shipment->getLocationId(),
                null,
                0 - $shipment->getQuantity(),
                $timestamp,
                $result->getValuationMethod(),
                $result->getAllocations(),
                $result->getTotalCost(),
                $shipment->getSourceDocument(),
                $result->getCurrency(),
                array(
                    'total_issued_quantity' => $result->getAllocatedQuantity(),
                    'total_issued_cost' => $result->getTotalCost(),
                    'average_unit_cost' => $result->getAverageUnitCost(),
                )
            );

            $self->movements->saveMovement($movement);
            foreach ($result->getAllocations() as $allocation) {
                $self->movements->saveCostAllocation($operationId, $allocation);
            }

            if ($result->getValuationMethod() === ValuationMethod::AVERAGE) {
                $metadata = $result->getMetadata();
                $snapshot = new InventoryValuationSnapshot(
                    $self->ids->generate('snapshot'),
                    $operationId,
                    $shipment->getSku(),
                    $shipment->getWarehouseId(),
                    $shipment->getLocationId(),
                    $result->getValuationMethod(),
                    $result->getAverageUnitCost(),
                    $result->getCurrency(),
                    isset($metadata['basis_quantity']) ? $metadata['basis_quantity'] : 0,
                    isset($metadata['basis_cost']) ? $metadata['basis_cost'] : 0,
                    $timestamp
                );
                $self->snapshots->saveSnapshot($snapshot);
            }

            $self->events->dispatch('inventory.shipped', $movement->toArray());
            $self->logger->info('Stock shipped.', array('operation_id' => $operationId));

            return $result;
        });
    }
}
