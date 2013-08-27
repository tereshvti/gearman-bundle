<?php

if (!file_exists($autoload = __DIR__ . '/../vendor/autoload.php')) {
    die("Run 'php composer.phar install --dev' first");
}

$loader = require_once $autoload;
$loader->add('Supertag\Bundle\GearmanBundle', __DIR__ . '/..');
$loader->add('Acme', __DIR__ . '/sf2app/src');

use Doctrine\Common\Annotations\AnnotationRegistry;

AnnotationRegistry::registerFile(__DIR__.'/../Annotation/All.php');

