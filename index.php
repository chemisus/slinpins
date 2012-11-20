<?php
require_once('src/Scope.php');
require_once('src/Provider.php');
require_once('src/ConstantProvider.php');
require_once('src/VariableProvider.php');
require_once('src/MethodProvider.php');
require_once('src/FactoryProvider.php');
require_once('src/ServiceProvider.php');
require_once('src/Slinpin.php');

$method = new \MethodProvider(function ($a, $b) {
    return $a.'+'.$b;
}, array(), array('a', 'b'));

echo $method(array(
    'a' => 'hah',
    'b' => 'blah',
));

print_r($method->values());
