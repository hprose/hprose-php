<?php
$loader = include __DIR__ . '/../vendor/autoload.php';

$psr4TestCaseClass = 'PHPUnit\Framework\TestCase';
$psr0TestCaseClass = 'PHPUnit_Framework_TestCase';

if (!class_exists($psr0TestCaseClass) && class_exists($psr4TestCaseClass)) {
    class_alias($psr4TestCaseClass, $psr0TestCaseClass);
}
