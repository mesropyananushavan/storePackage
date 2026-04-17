<?php

namespace StorePackage\WarehouseCore\Infrastructure\Pdo;

use StorePackage\WarehouseCore\Contracts\StockMovementRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\CostAllocation;
use StorePackage\WarehouseCore\Domain\Entity\StockMovement;

class PdoStockMovementRepository extends AbstractPdoRepository implements StockMovementRepositoryInterface
{
    /**
     * @param \PDO $pdo
     * @param array $tableNames
     */
    public function __construct($pdo, array $tableNames = array())
    {
        parent::__construct($pdo, $tableNames);
    }

    public function saveMovement(StockMovement $movement)
    {
        if ($this->movementExists($movement->getMovementId())) {
            $sql = 'UPDATE ' . $this->tableName('stock_movements', 'stock_movements') . ' SET '
                . 'operation_id = :operation_id, '
                . 'movement_type = :movement_type, '
                . 'sku = :sku, '
                . 'warehouse_id = :warehouse_id, '
                . 'location_id = :location_id, '
                . 'lot_id = :lot_id, '
                . 'quantity = :quantity, '
                . 'movement_timestamp = :movement_timestamp, '
                . 'valuation_method = :valuation_method, '
                . 'total_cost = :total_cost, '
                . 'source_document = :source_document, '
                . 'currency = :currency, '
                . 'metadata_json = :metadata_json '
                . 'WHERE movement_id = :movement_id';
        } else {
            $sql = 'INSERT INTO ' . $this->tableName('stock_movements', 'stock_movements') . ' ('
                . 'movement_id, operation_id, movement_type, sku, warehouse_id, location_id, lot_id, quantity, '
                . 'movement_timestamp, valuation_method, total_cost, source_document, currency, metadata_json'
                . ') VALUES ('
                . ':movement_id, :operation_id, :movement_type, :sku, :warehouse_id, :location_id, :lot_id, :quantity, '
                . ':movement_timestamp, :valuation_method, :total_cost, :source_document, :currency, :metadata_json'
                . ')';
        }

        $this->prepareAndExecute($sql, array(
            ':movement_id' => $movement->getMovementId(),
            ':operation_id' => $movement->getOperationId(),
            ':movement_type' => $movement->getMovementType(),
            ':sku' => $movement->getSku(),
            ':warehouse_id' => $movement->getWarehouseId(),
            ':location_id' => $this->mapNullableValue($movement->getLocationId()),
            ':lot_id' => $this->mapNullableValue($movement->getLotId()),
            ':quantity' => $movement->getQuantity(),
            ':movement_timestamp' => $movement->getTimestamp(),
            ':valuation_method' => $this->mapNullableValue($movement->getValuationMethod()),
            ':total_cost' => $movement->getTotalCost(),
            ':source_document' => $this->mapNullableValue($movement->getSourceDocument()),
            ':currency' => $this->mapNullableValue($movement->getCurrency()),
            ':metadata_json' => $this->encodeJsonArray($movement->getMetadata()),
        ));
    }

    public function saveCostAllocation($operationId, CostAllocation $allocation)
    {
        $sql = 'INSERT INTO ' . $this->tableName('stock_movement_cost_allocations', 'stock_movement_cost_allocations') . ' ('
            . 'operation_id, lot_id, quantity, unit_cost, total_cost, currency'
            . ') VALUES ('
            . ':operation_id, :lot_id, :quantity, :unit_cost, :total_cost, :currency'
            . ')';

        $this->prepareAndExecute($sql, array(
            ':operation_id' => $operationId,
            ':lot_id' => $allocation->getLotId(),
            ':quantity' => $allocation->getQuantity(),
            ':unit_cost' => $allocation->getUnitCost(),
            ':total_cost' => $allocation->getTotalCost(),
            ':currency' => $allocation->getCurrency(),
        ));
    }

    public function findByOperationId($operationId)
    {
        $sql = 'SELECT * FROM ' . $this->tableName('stock_movements', 'stock_movements')
            . ' WHERE operation_id = :operation_id ORDER BY movement_timestamp ASC, movement_id ASC';
        $rows = $this->prepareAndExecute($sql, array(':operation_id' => $operationId))->fetchAll(\PDO::FETCH_ASSOC);

        return $this->hydrateMovements($rows, array($operationId));
    }

    public function all()
    {
        $sql = 'SELECT * FROM ' . $this->tableName('stock_movements', 'stock_movements')
            . ' ORDER BY movement_timestamp ASC, movement_id ASC';
        $rows = $this->prepareAndExecute($sql, array())->fetchAll(\PDO::FETCH_ASSOC);

        $operationIds = array();
        foreach ($rows as $row) {
            $operationIds[$row['operation_id']] = $row['operation_id'];
        }

        return $this->hydrateMovements($rows, array_values($operationIds));
    }

    private function movementExists($movementId)
    {
        $sql = 'SELECT movement_id FROM ' . $this->tableName('stock_movements', 'stock_movements')
            . ' WHERE movement_id = :movement_id';

        return $this->prepareAndExecute($sql, array(':movement_id' => $movementId))->fetch(\PDO::FETCH_ASSOC) !== false;
    }

    private function hydrateMovements(array $rows, array $operationIds)
    {
        $allocationsByOperation = $this->loadAllocationsByOperation($operationIds);
        $movements = array();

        foreach ($rows as $row) {
            $operationId = $row['operation_id'];
            $movements[] = new StockMovement(
                $row['movement_id'],
                $operationId,
                $row['movement_type'],
                $row['sku'],
                $row['warehouse_id'],
                isset($row['location_id']) ? $row['location_id'] : null,
                isset($row['lot_id']) ? $row['lot_id'] : null,
                $row['quantity'],
                $row['movement_timestamp'],
                isset($row['valuation_method']) ? $row['valuation_method'] : null,
                isset($allocationsByOperation[$operationId]) ? $allocationsByOperation[$operationId] : array(),
                $row['total_cost'],
                isset($row['source_document']) ? $row['source_document'] : null,
                isset($row['currency']) ? $row['currency'] : null,
                $this->decodeJsonArray(isset($row['metadata_json']) ? $row['metadata_json'] : null)
            );
        }

        return $movements;
    }

    private function loadAllocationsByOperation(array $operationIds)
    {
        if (empty($operationIds)) {
            return array();
        }

        $params = array();
        $sql = 'SELECT * FROM ' . $this->tableName('stock_movement_cost_allocations', 'stock_movement_cost_allocations')
            . ' WHERE operation_id IN ' . $this->buildInClause($operationIds, $params, 'operation')
            . ' ORDER BY operation_id ASC, lot_id ASC';
        $rows = $this->prepareAndExecute($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
        $allocationsByOperation = array();

        foreach ($rows as $row) {
            if (!isset($allocationsByOperation[$row['operation_id']])) {
                $allocationsByOperation[$row['operation_id']] = array();
            }

            $allocationsByOperation[$row['operation_id']][] = new CostAllocation(
                $row['lot_id'],
                $row['quantity'],
                $row['unit_cost'],
                $row['total_cost'],
                $row['currency']
            );
        }

        return $allocationsByOperation;
    }
}
