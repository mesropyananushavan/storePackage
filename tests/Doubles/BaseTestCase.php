<?php

namespace StorePackage\WarehouseCore\Tests\Doubles;

if (class_exists('PHPUnit\Framework\TestCase')) {
    abstract class BaseTestCase extends \PHPUnit\Framework\TestCase
    {
    }
} else {
    abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
    {
    }
}
