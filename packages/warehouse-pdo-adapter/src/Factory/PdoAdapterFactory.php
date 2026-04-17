<?php

namespace StorePackage\WarehousePdoAdapter\Factory;

use PDO;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoInventoryLotRepository;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoInventoryValuationSnapshotRepository;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoReservationRepository;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoStockMovementRepository;
use StorePackage\WarehouseCore\Infrastructure\Pdo\PdoTransactionManager;
use StorePackage\WarehousePdoAdapter\Config\PdoAdapterConfig;

class PdoAdapterFactory
{
    /**
     * @param PdoAdapterConfig $config
     *
     * @return array
     */
    public function create(PdoAdapterConfig $config)
    {
        $pdo = new PDO(
            $config->getDsn(),
            $config->getUsername(),
            $config->getPassword(),
            $config->getPdoOptions()
        );

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->applySessionDefaults($pdo, $config);

        return array(
            'pdo' => $pdo,
            'inventory_lot_repository' => new PdoInventoryLotRepository($pdo, $config->getTableNames()),
            'stock_movement_repository' => new PdoStockMovementRepository($pdo, $config->getTableNames()),
            'reservation_repository' => new PdoReservationRepository($pdo, $config->getTableNames()),
            'valuation_snapshot_repository' => new PdoInventoryValuationSnapshotRepository($pdo, $config->getTableNames()),
            'transaction_manager' => new PdoTransactionManager($pdo),
        );
    }

    /**
     * @param PDO $pdo
     * @param PdoAdapterConfig $config
     *
     * @return void
     */
    private function applySessionDefaults(PDO $pdo, PdoAdapterConfig $config)
    {
        $driverName = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $lockTimeoutSeconds = $config->getLockTimeoutSeconds();

        if ($lockTimeoutSeconds !== null) {
            if ($driverName === 'mysql') {
                $pdo->exec('SET SESSION innodb_lock_wait_timeout = ' . (int) $lockTimeoutSeconds);
            } elseif ($driverName === 'pgsql') {
                $pdo->exec("SET lock_timeout = '" . ((int) $lockTimeoutSeconds * 1000) . "ms'");
            }
        }

        foreach ($config->getSessionStatements() as $statement) {
            $pdo->exec($statement);
        }
    }
}
