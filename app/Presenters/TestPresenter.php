<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Utils\Random;
use Tracy\Debugger;

class TestPresenter extends Presenter
{
    use LayoutTrait;

    public function renderLog(): void
    {
        $logContent = Random::generate(30);
        Debugger::log($logContent);
        $this->template->logContent = $logContent;
    }
}
