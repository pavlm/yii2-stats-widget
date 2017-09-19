<?php

use Composer\Autoload\ClassLoader;

require $_ENV['vendorDir'] . '/autoload.php';

$loader = new ClassLoader();
$loader->addPsr4('pavlm\\yii\\stats\\', dirname(__DIR__));
$loader->register();