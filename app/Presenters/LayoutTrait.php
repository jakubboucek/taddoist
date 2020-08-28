<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Components\VersionRenderer;

trait LayoutTrait
{
    /** @var VersionRenderer */
    private $versionRenderer;

    public function injectVersionRenderer(VersionRenderer $versionRenderer): void
    {
        $this->versionRenderer = $versionRenderer;
    }

    public function createComponentVersion(): VersionRenderer
    {
        return $this->versionRenderer;
    }
}
