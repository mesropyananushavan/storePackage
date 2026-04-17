<?php

namespace StorePackage\WarehouseCore\Infrastructure\Pdo;

use StorePackage\WarehouseCore\Contracts\ReservationRepositoryInterface;
use StorePackage\WarehouseCore\Domain\Entity\Reservation;

class PdoReservationRepository extends AbstractPdoRepository implements ReservationRepositoryInterface
{
    /**
     * @param \PDO $pdo
     * @param array $tableNames
     */
    public function __construct($pdo, array $tableNames = array())
    {
        parent::__construct($pdo, $tableNames);
    }

    public function findReservation($reservationId)
    {
        $sql = 'SELECT * FROM ' . $this->tableName('reservations', 'reservations')
            . ' WHERE reservation_id = :reservation_id';
        $row = $this->prepareAndExecute($sql, array(':reservation_id' => $reservationId))->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrateReservation($row);
    }

    public function saveReservation(Reservation $reservation)
    {
        if ($this->findReservation($reservation->getReservationId()) === null) {
            $sql = 'INSERT INTO ' . $this->tableName('reservations', 'reservations') . ' ('
                . 'reservation_id, sku, warehouse_id, location_id, quantity, released_quantity, '
                . 'reference, status, reserved_at, released_at'
                . ') VALUES ('
                . ':reservation_id, :sku, :warehouse_id, :location_id, :quantity, :released_quantity, '
                . ':reference, :status, :reserved_at, :released_at'
                . ')';
        } else {
            $sql = 'UPDATE ' . $this->tableName('reservations', 'reservations') . ' SET '
                . 'sku = :sku, '
                . 'warehouse_id = :warehouse_id, '
                . 'location_id = :location_id, '
                . 'quantity = :quantity, '
                . 'released_quantity = :released_quantity, '
                . 'reference = :reference, '
                . 'status = :status, '
                . 'reserved_at = :reserved_at, '
                . 'released_at = :released_at '
                . 'WHERE reservation_id = :reservation_id';
        }

        $this->prepareAndExecute($sql, $this->reservationParams($reservation));
    }

    public function getReservedQuantity($sku, $warehouseId, $locationId)
    {
        $params = array(
            ':sku' => $sku,
            ':warehouse_id' => $warehouseId,
        );
        $sql = 'SELECT SUM(quantity - released_quantity) AS reserved_quantity '
            . 'FROM ' . $this->tableName('reservations', 'reservations')
            . ' WHERE sku = :sku AND warehouse_id = :warehouse_id AND (quantity - released_quantity) > 0';

        if ($locationId !== null) {
            $sql .= ' AND location_id = :location_id';
            $params[':location_id'] = $locationId;
        }

        $row = $this->prepareAndExecute($sql, $params)->fetch(\PDO::FETCH_ASSOC);
        if ($row === false || $row['reserved_quantity'] === null) {
            return 0.0;
        }

        return (float) $row['reserved_quantity'];
    }

    public function findActiveReservationsBySku($sku, $warehouseId, $locationId)
    {
        $params = array(
            ':sku' => $sku,
            ':warehouse_id' => $warehouseId,
        );
        $sql = 'SELECT * FROM ' . $this->tableName('reservations', 'reservations')
            . ' WHERE sku = :sku AND warehouse_id = :warehouse_id AND (quantity - released_quantity) > 0';

        if ($locationId !== null) {
            $sql .= ' AND location_id = :location_id';
            $params[':location_id'] = $locationId;
        }

        $sql .= ' ORDER BY reserved_at ASC, reservation_id ASC';

        $rows = $this->prepareAndExecute($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        return $this->hydrateReservations($rows);
    }

    public function all()
    {
        $sql = 'SELECT * FROM ' . $this->tableName('reservations', 'reservations')
            . ' ORDER BY reserved_at ASC, reservation_id ASC';
        $rows = $this->prepareAndExecute($sql, array())->fetchAll(\PDO::FETCH_ASSOC);

        return $this->hydrateReservations($rows);
    }

    private function hydrateReservations(array $rows)
    {
        $reservations = array();

        foreach ($rows as $row) {
            $reservations[] = $this->hydrateReservation($row);
        }

        return $reservations;
    }

    private function hydrateReservation(array $row)
    {
        return new Reservation(
            $row['reservation_id'],
            $row['sku'],
            $row['warehouse_id'],
            isset($row['location_id']) ? $row['location_id'] : null,
            $row['quantity'],
            $row['released_quantity'],
            isset($row['reference']) ? $row['reference'] : null,
            $row['status'],
            $row['reserved_at'],
            isset($row['released_at']) ? $row['released_at'] : null
        );
    }

    private function reservationParams(Reservation $reservation)
    {
        return array(
            ':reservation_id' => $reservation->getReservationId(),
            ':sku' => $reservation->getSku(),
            ':warehouse_id' => $reservation->getWarehouseId(),
            ':location_id' => $this->mapNullableValue($reservation->getLocationId()),
            ':quantity' => $reservation->getQuantity(),
            ':released_quantity' => $reservation->getReleasedQuantity(),
            ':reference' => $this->mapNullableValue($reservation->getReference()),
            ':status' => $reservation->getStatus(),
            ':reserved_at' => $reservation->getReservedAt(),
            ':released_at' => $this->mapNullableValue($reservation->getReleasedAt()),
        );
    }
}
