<?php

namespace StorePackage\WarehouseCore\Infrastructure\Pdo;

abstract class AbstractPdoRepository
{
    private $pdo;
    private $tableNames;

    /**
     * @param \PDO $pdo
     * @param array $tableNames
     */
    public function __construct($pdo, array $tableNames)
    {
        $this->pdo = $pdo;
        $this->tableNames = $tableNames;
    }

    /**
     * @return \PDO
     */
    protected function getPdo()
    {
        return $this->pdo;
    }

    protected function tableName($logicalName, $defaultName)
    {
        return isset($this->tableNames[$logicalName]) ? $this->tableNames[$logicalName] : $defaultName;
    }

    protected function driverName()
    {
        return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    protected function supportsForUpdate()
    {
        $driver = $this->driverName();

        return in_array($driver, array('mysql', 'pgsql', 'oci', 'sqlsrv'), true);
    }

    protected function decodeJsonArray($value)
    {
        if ($value === null || $value === '') {
            return array();
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return array();
        }

        return $decoded;
    }

    protected function encodeJsonArray(array $value)
    {
        return json_encode($value);
    }

    protected function mapNullableValue($value)
    {
        if ($value === null) {
            return null;
        }

        return $value;
    }

    protected function buildInClause(array $values, array &$params, $prefix)
    {
        $placeholders = array();
        $index = 0;

        foreach ($values as $value) {
            $name = ':' . $prefix . '_' . $index;
            $params[$name] = $value;
            $placeholders[] = $name;
            $index = $index + 1;
        }

        if (empty($placeholders)) {
            return '(NULL)';
        }

        return '(' . implode(', ', $placeholders) . ')';
    }

    protected function prepareAndExecute($sql, array $params)
    {
        $statement = $this->pdo->prepare($sql);
        foreach ($params as $name => $value) {
            $statement->bindValue($name, $value, $value === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
        }

        $statement->execute();

        return $statement;
    }
}
