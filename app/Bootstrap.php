<?php

declare(strict_types=1);

namespace App;

use App\Model\Google\Logger;
use Google\Cloud\Logging\LoggingClient;
use Nette\Configurator;
use Tracy\Debugger;


class Bootstrap
{
    public static function boot(): Configurator
    {
        $configurator = new Configurator();

        // <Logger shitcode=true>
        $logging = new LoggingClient(
            ['keyFilePath' => __DIR__ . '/Config/google-cloud-credentials.json']
        );
        Debugger::setLogger(new Logger($logging->logger('nette')));
        // </Logger shitcode=true>

        $configurator->setDebugMode([])->enableDebugger();

        $tempDir = __DIR__ . '/../temp';
        if (!is_writable($tempDir)) {
            $tempDir = sys_get_temp_dir();
        }

        $configurator->setTimeZone('Europe/Prague');
        $configurator->setTempDirectory($tempDir);
        $configurator->createRobotLoader()->addDirectory(__DIR__)->register();

        $configurator->addConfig(__DIR__ . '/Config/config.neon');
        $configurator->addConfig(__DIR__ . '/Config/config.local.neon');

        return $configurator;
    }
}