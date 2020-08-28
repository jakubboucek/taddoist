<?php

declare(strict_types=1);

namespace App\Model\Google;

class AppVersionProvider
{
    private const VERSION_ENV_KEY = 'GAE_VERSION';


    public function getVersion(): ?string
    {
        $version = getenv(self::VERSION_ENV_KEY);
        if ($version === false) {
            return null;
        }

        return $version;
    }
}
