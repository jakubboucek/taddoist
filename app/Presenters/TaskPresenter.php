<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Model\CookieAuth;
use App\Model\InvalidUserIdException;
use App\Model\UserIdUndefinedException;
use Nette\Application\UI\Presenter;
use Nette\InvalidStateException;

class TaskPresenter extends Presenter
{
    /**
     * @var CookieAuth
     */
    private $authorizator;

    private $userId;


    public function __construct(CookieAuth $authorizator)
    {
        $this->authorizator = $authorizator;
        parent::__construct();
    }


    /**
     * @throws InvalidStateException
     * @throws InvalidUserIdException
     */
    protected function startup(): void
    {
        try {
            $this->userId = $this->authorizator->getId(CookieAuth::THROW_EXCEPTION);
        } catch (UserIdUndefinedException $e) {
            $backlink = $this->storeRequest();
            $this->redirect('Sign:todoist', ['backlink' => $backlink]);
        }

        parent::startup();
    }

}
