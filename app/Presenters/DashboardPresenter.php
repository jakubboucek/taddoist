<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Model\AccessTokenNotFoundException;
use App\Model\Bookmarklet;
use App\Model\Todoist;
use Nette\Application\UI\Presenter;
use Nette\Utils\JsonException;
use RuntimeException;


class DashboardPresenter extends Presenter
{
    /**
     * @var Todoist\ClientFactory
     */
    private $todoistClientFactory;


    public function __construct(Todoist\ClientFactory $todoistClientFactory)
    {
        $this->todoistClientFactory = $todoistClientFactory;
        parent::__construct();
    }


    protected function startup()
    {
        if ($this->user->loggedIn !== true) {
            $backlink = $this->storeRequest();
            $this->redirect('Sign:google', ['backlink' => $backlink]);
        }
        parent::startup();
    }


    /**
     * @throws JsonException
     * @throws RuntimeException
     * @throws \App\Model\UserRequiredLoggedInFirstException
     * @throws \Nette\Application\UI\InvalidLinkException
     */
    public function renderDefault()
    {
        try {
            $projects = $this->findProjects();
        } catch (AccessTokenNotFoundException $e) {
            $backlink = $this->storeRequest();
            $this->redirect('Sign:todoist', ['backlink' => $backlink]);
        }

        $links = [];

        foreach ($projects as $project) {
            $links[] = [$project['name'], $this->getBookmarklet((string)$project['id'])];
        }


        $this->template->baseLink = $this->getBookmarklet();
        $this->template->projectLinks = $links;
    }


    /**
     * @return array
     * @throws \App\Model\AccessTokenNotFoundException
     * @throws \Nette\Utils\JsonException
     * @throws \App\Model\UserRequiredLoggedInFirstException
     * @throws RuntimeException
     */
    private function findProjects(): array
    {
        $todoist = $this->todoistClientFactory->create();
        return $todoist->findProjects();
    }


    /**
     * @param null|string $projectId
     * @return string
     * @throws \Nette\Application\UI\InvalidLinkException
     * @throws \Nette\IOException
     * @throws \Nette\Utils\JsonException
     */
    private function getBookmarklet(?string $projectId = null): string
    {
        $addEndpoint = $this->link('//:Task:create');
        return Bookmarklet\Generator::generate($addEndpoint, $projectId);
    }
}
