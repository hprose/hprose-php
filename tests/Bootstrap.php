<?php
$loader = include __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

if (!class_exists(PHPUnit_Framework_TestCase::class) && class_exists(TestCase::class)) {
    class_alias(TestCase::class, PHPUnit_Framework_TestCase::class);
}
