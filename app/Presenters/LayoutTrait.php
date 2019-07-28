<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Components\VersionRenderer;

trait LayoutTrait
{
    public function createComponentVersion(): VersionRenderer
    {
        return new VersionRenderer();
    }
}
