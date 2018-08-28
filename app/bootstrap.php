<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$configurator = new \Nette\Configurator();

$configurator->setDebugMode([])->enableDebugger();

$tempDir = __DIR__ . '/../temp';
if(!is_writable($tempDir)){
    $tempDir = sys_get_temp_dir();
}

$configurator->setTimeZone('Europe/Prague');
$configurator->setTempDirectory($tempDir);
$configurator->createRobotLoader()->addDirectory(__DIR__)->register();

$configurator->addConfig(__DIR__ . '/Config/config.neon');
$configurator->addConfig(__DIR__ . '/Config/config.local.neon');

$container = $configurator->createContainer();

$container->getByType(\Nette\Application\Application::class)->run();