<?php

namespace StorePackage\WarehouseCore\Infrastructure\Pdo;

use StorePackage\WarehouseCore\Contracts\InventoryValuationSnapshotRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\InventoryValuationSnapshot;

class PdoInventoryValuationSnapshotRepository extends AbstractPdoRepository implements InventoryValuationSnapshotRepositoryInterface
{
    /**
     * @param \PDO $pdo
     * @param array $tableNames
     */
    public function __construct($pdo, array $tableNames = array())
    {
        parent::__construct($pdo, $tableNames);
    }

    public function saveSnapshot(InventoryValuationSnapshot $snapshot)
    {
        if ($this->findByOperationId($snapshot->getOperationId()) === null) {
            $sql = 'INSERT INTO ' . $this->tableName('valuation_snapshots', 'valuation_snapshots') . ' ('
                . 'snapshot_id, operation_id, sku, warehouse_id, location_id, valuation_method, '
                . 'average_unit_cost, currency, quantity_basis, total_cost_basis, created_at'
                . ') VALUES ('
                . ':snapshot_id, :operation_id, :sku, :warehouse_id, :location_id, :valuation_method, '
                . ':average_unit_cost, :currency, :quantity_basis, :total_cost_basis, :created_at'
                . ')';
        } else {
            $sql = 'UPDATE ' . $this->tableName('valuation_snapshots', 'valuation_snapshots') . ' SET '
                . 'snapshot_id = :snapshot_id, '
                . 'sku = :sku, '
                . 'warehouse_id = :warehouse_id, '
                . 'location_id = :location_id, '
                . 'valuation_method = :valuation_method, '
                . 'average_unit_cost = :average_unit_cost, '
                . 'currency = :currency, '
                . 'quantity_basis = :quantity_basis, '
                . 'total_cost_basis = :total_cost_basis, '
                . 'created_at = :created_at '
                . 'WHERE operation_id = :operation_id';
        }

        $this->prepareAndExecute($sql, array(
            ':snapshot_id' => $snapshot->getSnapshotId(),
            ':operation_id' => $snapshot->getOperationId(),
            ':sku' => $snapshot->getSku(),
            ':warehouse_id' => $snapshot->getWarehouseId(),
            ':location_id' => $this->mapNullableValue($snapshot->getLocationId()),
            ':valuation_method' => $snapshot->getValuationMethod(),
            ':average_unit_cost' => $snapshot->getAverageUnitCost(),
            ':currency' => $snapshot->getCurrency(),
            ':quantity_basis' => $snapshot->getQuantityBasis(),
            ':total_cost_basis' => $snapshot->getTotalCostBasis(),
            ':created_at' => $snapshot->getCreatedAt(),
        ));
    }

    public function findByOperationId($operationId)
    {
        $sql = 'SELECT * FROM ' . $this->tableName('valuation_snapshots', 'valuation_snapshots')
            . ' WHERE operation_id = :operation_id';
        $row = $this->prepareAndExecute($sql, array(':operation_id' => $operationId))->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new InventoryValuationSnapshot(
            $row['snapshot_id'],
            $row['operation_id'],
            $row['sku'],
            $row['warehouse_id'],
            isset($row['location_id']) ? $row['location_id'] : null,
            $row['valuation_method'],
            $row['average_unit_cost'],
            $row['currency'],
            $row['quantity_basis'],
            $row['total_cost_basis'],
            $row['created_at']
        );
    }

    public function all()
    {
        $sql = 'SELECT * FROM ' . $this->tableName('valuation_snapshots', 'valuation_snapshots')
            . ' ORDER BY created_at ASC, snapshot_id ASC';
        $rows = $this->prepareAndExecute($sql, array())->fetchAll(\PDO::FETCH_ASSOC);
        $snapshots = array();

        foreach ($rows as $row) {
            $snapshots[] = new InventoryValuationSnapshot(
                $row['snapshot_id'],
                $row['operation_id'],
                $row['sku'],
                $row['warehouse_id'],
                isset($row['location_id']) ? $row['location_id'] : null,
                $row['valuation_method'],
                $row['average_unit_cost'],
                $row['currency'],
                $row['quantity_basis'],
                $row['total_cost_basis'],
                $row['created_at']
            );
        }

        return $snapshots;
    }
}
