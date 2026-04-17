<?php

namespace StorePackage\WarehousePdoAdapter\Config;

class PdoAdapterConfig
{
    /** @var string */
    private $dsn;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var array */
    private $pdoOptions;

    /** @var array */
    private $tableNames;

    /** @var int|null */
    private $lockTimeoutSeconds;

    /** @var int */
    private $deadlockRetryAttempts;

    /** @var int */
    private $deadlockRetryDelayMilliseconds;

    /** @var array */
    private $sessionStatements;

    /**
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $pdoOptions
     * @param array $tableNames
     * @param int|null $lockTimeoutSeconds
     * @param int $deadlockRetryAttempts
     * @param int $deadlockRetryDelayMilliseconds
     * @param array $sessionStatements
     */
    public function __construct(
        $dsn,
        $username,
        $password,
        array $pdoOptions,
        array $tableNames,
        $lockTimeoutSeconds,
        $deadlockRetryAttempts,
        $deadlockRetryDelayMilliseconds,
        array $sessionStatements
    ) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->pdoOptions = $pdoOptions;
        $this->tableNames = $tableNames;
        $this->lockTimeoutSeconds = $lockTimeoutSeconds;
        $this->deadlockRetryAttempts = (int) $deadlockRetryAttempts;
        $this->deadlockRetryDelayMilliseconds = (int) $deadlockRetryDelayMilliseconds;
        $this->sessionStatements = $sessionStatements;
    }

    /**
     * @return PdoAdapterConfig
     */
    public static function createDefault($dsn, $username, $password)
    {
        return new self($dsn, $username, $password, array(), array(), null, 0, 0, array());
    }

    /**
     * @return string
     */
    public function getDsn()
    {
        return $this->dsn;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return array
     */
    public function getPdoOptions()
    {
        return $this->pdoOptions;
    }

    /**
     * @return array
     */
    public function getTableNames()
    {
        return $this->tableNames;
    }

    /**
     * @return int|null
     */
    public function getLockTimeoutSeconds()
    {
        return $this->lockTimeoutSeconds;
    }

    /**
     * @return int
     */
    public function getDeadlockRetryAttempts()
    {
        return $this->deadlockRetryAttempts;
    }

    /**
     * @return int
     */
    public function getDeadlockRetryDelayMilliseconds()
    {
        return $this->deadlockRetryDelayMilliseconds;
    }

    /**
     * @return array
     */
    public function getSessionStatements()
    {
        return $this->sessionStatements;
    }
}
