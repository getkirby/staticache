<?php

use Kirby\Cms\App;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/index.php';

// leave errors, warnings and deprecations to PHPUnit
App::$enableWhoops = false;
