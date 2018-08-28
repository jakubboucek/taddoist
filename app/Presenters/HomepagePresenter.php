<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Model\Bookmarklet;
use Nette\Application\UI\Presenter;

class HomepagePresenter extends Presenter
{
    public function renderDefault()
    {
        $addEndpoint = $this->link('//Task:create');
        $addLink = Bookmarklet\Generator::generate($addEndpoint);
        $this->template->addUrl = $addLink;
    }
}
