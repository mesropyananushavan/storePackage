<?php

namespace StorePackage\WarehouseCore\Infrastructure\Pdo;

use StorePackage\WarehouseCore\Contracts\TransactionManagerInterface;
use StorePackage\WarehouseCore\Domain\Exception\ConcurrencyException;

class PdoTransactionManager implements TransactionManagerInterface
{
    private $pdo;

    /**
     * @param \PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function transactional($operation)
    {
        if ($this->pdo->inTransaction()) {
            return call_user_func($operation);
        }

        $this->pdo->beginTransaction();

        try {
            $result = call_user_func($operation);
            $this->pdo->commit();

            return $result;
        } catch (\Exception $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $this->rethrow($exception);
        }
    }

    private function rethrow(\Exception $exception)
    {
        if ($exception instanceof \PDOException) {
            $code = (string) $exception->getCode();
            if ($code === '40001' || $code === '40P01') {
                throw new ConcurrencyException($exception->getMessage(), 0, $exception);
            }
        }

        throw $exception;
    }
}
