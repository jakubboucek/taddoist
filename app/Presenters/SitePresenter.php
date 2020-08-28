<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\UI\Presenter;

class SitePresenter extends Presenter
{
    use LayoutTrait;

    public function renderDefault(): void
    {
        if ($this->user->loggedIn === true) {
            $this->redirect('Dashboard:');
        }
    }
}
