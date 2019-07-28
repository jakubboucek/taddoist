<?php
declare(strict_types=1);

namespace App\Components;

use Nette\Application\UI\Control;

class VersionRenderer extends Control
{
    protected const VERSION_ENV_KEY = 'GAE_VERSION';
    protected const VERSION_UNKNOWN_VAL = '–';


    public function render(): void
    {
        echo $this->getVersion();
    }


    private function getVersion(): string
    {
        $version = getenv(self::VERSION_ENV_KEY);
        if ($version === false) {
            return self::VERSION_UNKNOWN_VAL;
        }

        return $version;
    }
}
