<?php

namespace StorePackage\WarehouseCore\Infrastructure\Pdo;

use StorePackage\WarehouseCore\Contracts\InventoryLotRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\InventoryLot;

class PdoInventoryLotRepository extends AbstractPdoRepository implements InventoryLotRepositoryInterface
{
    /**
     * @param \PDO $pdo
     * @param array $tableNames
     */
    public function __construct($pdo, array $tableNames = array())
    {
        parent::__construct($pdo, $tableNames);
    }

    public function findAvailableLotsBySku($sku, $warehouseId, $locationId)
    {
        return $this->findByScope($sku, $warehouseId, $locationId, null);
    }

    public function findAvailableLotsOrderedOldestFirst($sku, $warehouseId, $locationId)
    {
        return $this->findByScope($sku, $warehouseId, $locationId, 'ASC');
    }

    public function findAvailableLotsOrderedNewestFirst($sku, $warehouseId, $locationId)
    {
        return $this->findByScope($sku, $warehouseId, $locationId, 'DESC');
    }

    public function getWeightedAverageCost($sku, $warehouseId, $locationId)
    {
        $params = array(
            ':sku' => $sku,
            ':warehouse_id' => $warehouseId,
        );
        $sql = 'SELECT SUM(quantity_remaining) AS total_quantity, '
            . 'SUM(quantity_remaining * unit_cost) AS total_cost '
            . 'FROM ' . $this->tableName('inventory_lots', 'inventory_lots')
            . ' WHERE sku = :sku AND warehouse_id = :warehouse_id AND quantity_remaining > 0';

        if ($locationId !== null) {
            $sql .= ' AND location_id = :location_id';
            $params[':location_id'] = $locationId;
        }

        $row = $this->prepareAndExecute($sql, $params)->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return 0.0;
        }

        $quantity = isset($row['total_quantity']) ? (float) $row['total_quantity'] : 0.0;
        $cost = isset($row['total_cost']) ? (float) $row['total_cost'] : 0.0;
        if ($quantity <= 0) {
            return 0.0;
        }

        return round($cost / $quantity, 6);
    }

    public function saveInventoryLot(InventoryLot $lot)
    {
        if ($this->findById($lot->getLotId()) === null) {
            $sql = 'INSERT INTO ' . $this->tableName('inventory_lots', 'inventory_lots') . ' ('
                . 'lot_id, sku, warehouse_id, location_id, received_at, quantity_received, quantity_remaining, '
                . 'unit_cost, currency, source_document, parent_lot_id'
                . ') VALUES ('
                . ':lot_id, :sku, :warehouse_id, :location_id, :received_at, :quantity_received, :quantity_remaining, '
                . ':unit_cost, :currency, :source_document, :parent_lot_id'
                . ')';
        } else {
            $sql = 'UPDATE ' . $this->tableName('inventory_lots', 'inventory_lots') . ' SET '
                . 'sku = :sku, '
                . 'warehouse_id = :warehouse_id, '
                . 'location_id = :location_id, '
                . 'received_at = :received_at, '
                . 'quantity_received = :quantity_received, '
                . 'quantity_remaining = :quantity_remaining, '
                . 'unit_cost = :unit_cost, '
                . 'currency = :currency, '
                . 'source_document = :source_document, '
                . 'parent_lot_id = :parent_lot_id '
                . 'WHERE lot_id = :lot_id';
        }

        $this->prepareAndExecute($sql, $this->inventoryLotParams($lot));
    }

    public function saveInventoryLots(array $lots)
    {
        foreach ($lots as $lot) {
            $this->saveInventoryLot($lot);
        }
    }

    public function lockLotsForUpdate(array $lotIds)
    {
        if (empty($lotIds)) {
            return array();
        }

        $params = array();
        $sql = 'SELECT * FROM ' . $this->tableName('inventory_lots', 'inventory_lots')
            . ' WHERE lot_id IN ' . $this->buildInClause($lotIds, $params, 'lot_id')
            . ' ORDER BY received_at ASC, lot_id ASC';

        if ($this->supportsForUpdate() && $this->getPdo()->inTransaction()) {
            $sql .= ' FOR UPDATE';
        }

        $rows = $this->prepareAndExecute($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        return $this->hydrateInventoryLots($rows);
    }

    public function findById($lotId)
    {
        $sql = 'SELECT * FROM ' . $this->tableName('inventory_lots', 'inventory_lots') . ' WHERE lot_id = :lot_id';
        $row = $this->prepareAndExecute($sql, array(':lot_id' => $lotId))->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrateInventoryLot($row);
    }

    public function all()
    {
        $sql = 'SELECT * FROM ' . $this->tableName('inventory_lots', 'inventory_lots')
            . ' ORDER BY received_at ASC, lot_id ASC';
        $rows = $this->prepareAndExecute($sql, array())->fetchAll(\PDO::FETCH_ASSOC);

        return $this->hydrateInventoryLots($rows);
    }

    private function findByScope($sku, $warehouseId, $locationId, $direction)
    {
        $params = array(
            ':sku' => $sku,
            ':warehouse_id' => $warehouseId,
        );
        $sql = 'SELECT * FROM ' . $this->tableName('inventory_lots', 'inventory_lots')
            . ' WHERE sku = :sku AND warehouse_id = :warehouse_id AND quantity_remaining > 0';

        if ($locationId !== null) {
            $sql .= ' AND location_id = :location_id';
            $params[':location_id'] = $locationId;
        }

        if ($direction !== null) {
            $sql .= ' ORDER BY received_at ' . $direction . ', lot_id ' . $direction;
        }

        $rows = $this->prepareAndExecute($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        return $this->hydrateInventoryLots($rows);
    }

    private function hydrateInventoryLots(array $rows)
    {
        $lots = array();

        foreach ($rows as $row) {
            $lots[] = $this->hydrateInventoryLot($row);
        }

        return $lots;
    }

    private function hydrateInventoryLot(array $row)
    {
        return new InventoryLot(
            $row['lot_id'],
            $row['sku'],
            $row['warehouse_id'],
            isset($row['location_id']) ? $row['location_id'] : null,
            $row['received_at'],
            $row['quantity_received'],
            $row['quantity_remaining'],
            $row['unit_cost'],
            $row['currency'],
            isset($row['source_document']) ? $row['source_document'] : null,
            isset($row['parent_lot_id']) ? $row['parent_lot_id'] : null
        );
    }

    private function inventoryLotParams(InventoryLot $lot)
    {
        return array(
            ':lot_id' => $lot->getLotId(),
            ':sku' => $lot->getSku(),
            ':warehouse_id' => $lot->getWarehouseId(),
            ':location_id' => $this->mapNullableValue($lot->getLocationId()),
            ':received_at' => $lot->getReceivedAt(),
            ':quantity_received' => $lot->getQuantityReceived(),
            ':quantity_remaining' => $lot->getQuantityRemaining(),
            ':unit_cost' => $lot->getUnitCost(),
            ':currency' => $lot->getCurrency(),
            ':source_document' => $this->mapNullableValue($lot->getSourceDocument()),
            ':parent_lot_id' => $this->mapNullableValue($lot->getParentLotId()),
        );
    }
}
