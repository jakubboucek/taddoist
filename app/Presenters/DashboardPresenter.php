<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Model\AccessTokenNotFoundException;
use App\Model\ApiForbiddenException;
use App\Model\Bookmarklet;
use App\Model\Todoist;
use App\Model\UserRequiredLoggedInFirstException;
use Nette\Application\UI\Presenter;
use Nette\Utils\JsonException;
use RuntimeException;
use Tracy\Debugger;


class DashboardPresenter extends Presenter
{
    /**
     * @var Todoist\ClientFactory
     */
    private $todoistClientFactory;

    /**
     * @var array
     */
    private $projects;


    /**
     * @param Todoist\ClientFactory $todoistClientFactory
     */
    public function __construct(Todoist\ClientFactory $todoistClientFactory)
    {
        $this->todoistClientFactory = $todoistClientFactory;
        parent::__construct();
    }


    /**
     * @throws JsonException
     * @throws RuntimeException
     */
    protected function startup()
    {
        try {
            if ($this->user->loggedIn !== true) {
                throw new UserRequiredLoggedInFirstException('StartUp: User not logged');
            }

            $this->projects = $this->findProjects();
        } catch (UserRequiredLoggedInFirstException $e) {
            Debugger::log(sprintf('%s: #%d %s', \get_class($e), $e->getCode(), $e->getMessage()));
            $backlink = $this->storeRequest();
            $this->redirect('Sign:google', ['backlink' => $backlink]);
        } catch (AccessTokenNotFoundException $e) {
            Debugger::log(sprintf('%s: #%d %s', \get_class($e), $e->getCode(), $e->getMessage()));
            $backlink = $this->storeRequest();
            $this->redirect('Sign:todoist', ['backlink' => $backlink]);
        } catch (ApiForbiddenException $e) {
            Debugger::log(sprintf('%s: #%d %s', \get_class($e), $e->getCode(), $e->getMessage()));
            $backlink = $this->storeRequest();
            $this->redirect('Sign:todoist', ['backlink' => $backlink]);
        }

        parent::startup();
    }


    /**
     * @throws JsonException
     * @throws RuntimeException
     * @throws \Nette\Application\UI\InvalidLinkException
     */
    public function renderDefault(): void
    {
        $links = [];

        foreach ($this->projects as $project) {
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
