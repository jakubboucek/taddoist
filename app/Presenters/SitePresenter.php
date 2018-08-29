<?php
declare(strict_types=1);

namespace App\Presenters;

use Nette\Application\UI\Presenter;

class SitePresenter extends Presenter
{
    public function renderDefault()
    {
        if ($this->user->loggedIn === true) {
            $this->redirect('Dashboard:');
        }
    }
}
