<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\AccessTokenNotFoundException;
use App\Model\ApiForbiddenException;
use App\Model\Bookmarklet;
use App\Model\Todoist;
use App\Model\UserRequiredLoggedInFirstException;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Utils\JsonException;
use RuntimeException;
use Tracy\Debugger;


class DashboardPresenter extends Presenter
{
    use LayoutTrait;

    /** @var Todoist\ClientFactory */
    private $todoistClientFactory;

    /** @var array */
    private $projects;

    public function __construct(Todoist\ClientFactory $todoistClientFactory)
    {
        $this->todoistClientFactory = $todoistClientFactory;
        parent::__construct();
    }

    /**
     * @throws JsonException
     * @throws GuzzleException
     */
    protected function startup(): void
    {
        try {
            if ($this->user->loggedIn !== true) {
                throw new UserRequiredLoggedInFirstException('StartUp: User not logged');
            }

            $this->projects = $this->findProjects();
        } catch (UserRequiredLoggedInFirstException $e) {
            Debugger::log(sprintf('%s: #%d %s', get_class($e), $e->getCode(), $e->getMessage()));
            $backlink = $this->storeRequest();
            $this->redirect('Sign:google', ['backlink' => $backlink]);
        } catch (AccessTokenNotFoundException $e) {
            Debugger::log(sprintf('%s: #%d %s', get_class($e), $e->getCode(), $e->getMessage()));
            $backlink = $this->storeRequest();
            $this->redirect('Sign:todoist', ['backlink' => $backlink]);
        } catch (ApiForbiddenException $e) {
            Debugger::log(sprintf('%s: #%d %s', get_class($e), $e->getCode(), $e->getMessage()));
            $backlink = $this->storeRequest();
            $this->redirect('Sign:todoist', ['backlink' => $backlink]);
        }

        parent::startup();
    }

    /**
     * @throws JsonException
     * @throws RuntimeException
     * @throws InvalidLinkException
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
     * @throws JsonException
     * @throws GuzzleException
     */
    private function findProjects(): array
    {
        $todoist = $this->todoistClientFactory->create();
        return $todoist->findProjects();
    }

    /**
     * @param string|null $projectId
     * @return string
     * @throws InvalidLinkException
     * @throws JsonException
     */
    private function getBookmarklet(?string $projectId = null): string
    {
        $addEndpoint = $this->link('//:Task:create');
        return Bookmarklet\Generator::generate($addEndpoint, $projectId);
    }
}
