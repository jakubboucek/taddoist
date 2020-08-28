<?php

declare(strict_types=1);

namespace App\Components;

use App\Model\Google\AppVersionProvider;
use Nette\Application\UI\Control;

class VersionRenderer extends Control
{
    private const VERSION_UNKNOWN_VAL = '<unknown>';

    /** @var AppVersionProvider */
    private $versionProvider;

    public function __construct(AppVersionProvider $versionProvider)
    {
        $this->versionProvider = $versionProvider;
        parent::__construct();
    }

    public function render(): void
    {
        echo $this->versionProvider->getVersion() ?? self::VERSION_UNKNOWN_VAL;
    }


}
