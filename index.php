<?php
require_once('src/Scope.php');
require_once('src/Provider.php');
require_once('src/ConstantProvider.php');
require_once('src/VariableProvider.php');
require_once('src/MethodProvider.php');
require_once('src/FactoryProvider.php');
require_once('src/ServiceProvider.php');
require_once('src/Slinpin.php');

$scope = new \Slinpins();

$scope->provider('a', new \ConstantProvider('A'));

$scope->provider('b', new \MethodProvider(function ($a) {
    return $a;
}));

print_r($scope->b('B'));