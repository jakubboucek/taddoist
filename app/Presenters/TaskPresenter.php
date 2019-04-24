<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Model\AccessTokenNotFoundException;
use App\Model\ApiForbiddenException;
use App\Model\ApiOperationException;
use App\Model\Todoist;
use App\Model\UserRequiredLoggedInFirstException;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Application\UI\Presenter;
use Nette\Utils\Html;
use Nette\Utils\JsonException;
use Nette\Utils\Random;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use RuntimeException;
use Tracy\Debugger;
use Tracy\ILogger;


class TaskPresenter extends Presenter
{
    /**
     * @var Todoist\ClientFactory
     */
    private $todoistClientFactory;


    public function __construct(Todoist\ClientFactory $todoistClientFactory)
    {
        parent::__construct();
        $this->todoistClientFactory = $todoistClientFactory;
    }


    /**
     */
    protected function startup(): void
    {
        if ($this->user->loggedIn !== true) {
            $backlink = $this->storeRequest();
            $this->redirect('Sign:google', ['backlink' => $backlink]);
        }
        parent::startup();
    }


    /**
     * @param null|string $href
     * @param null|string $title
     * @param null|string $projectId
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Nette\Utils\JsonException
     */
    public function actionCreate(?string $href = null, ?string $title = null, ?string $projectId = null): void
    {
        if ($href === null) {
            $this->flashMessage('Vložený odkaz je neplatný', 'danger');
            $this->redirect('Site:');
        }

        if($projectId === null) {
            $this->forward('createProjects', $this->getHttpRequest()->getQuery());
        }

        /** @noinspection IsEmptyFunctionUsageInspection */
        $content = !empty($title) ? sprintf('[%s](%s)', $title, $href) : $href;

        try {
            $id = $this->createTask($content, (int)$projectId);
            Debugger::log(sprintf('Task created (taskId: %s)', $id));

            $url = "https://todoist.com/showTask?id=$id";
            $this->redirectUrl($url);
        } catch (UserRequiredLoggedInFirstException $e) {
            $backlink = $this->storeRequest();
            $this->flashMessage(
                'Prosíme, nejdříve se přihlašte. Po přihlášení bude aplikace pokračovat ve vytvoření úkolu.',
                'warning'
            );
            $this->redirect('Sign:google', ['backlink' => $backlink]);
        } catch (AccessTokenNotFoundException $e) {
            $backlink = $this->storeRequest();
            $this->flashMessage(
                'Prosíme, nejdříve si přidejte váš Todoist účet. Po přihlášení bude aplikace pokračovat ve vytvoření úkolu.',
                'warning'
            );
            $this->redirect('Sign:todoist', ['backlink' => $backlink]);
        } catch (RuntimeException $e) {
            Debugger::log($e, ILogger::EXCEPTION);
            $this->flashMessage(
                Html::el()
                    ->addText('Při vytváření úkolu došlo k neznámé chybě, zkuste to prosím znovu.')
                    ->addHtml(Html::el('br'))
                    ->addText('Chyba: ' . $e->getMessage()),
                'danger'
            );
            $this->forward('createError', $this->getHttpRequest()->getQuery());
        }
    }


    /**
     * @throws \Nette\Application\UI\InvalidLinkException
     */
    public function renderCreateError(): void
    {
        /** @var array $queries */
        $queries = $this->getHttpRequest()->getQuery();
        $queries['nonce'] = Random::generate();

        $this->template->retryLink = $this->link('create', $queries);
    }


    /**
     * @throws GuzzleException
     * @throws JsonException
     * @throws \Nette\Application\UI\InvalidLinkException
     */
    public function renderCreateProjects(): void
    {
        $parameters = $this->getHttpRequest()->getQuery();
        $links = [];


        try {
            $projects = $this->todoistClientFactory->create()->findProjects();

            foreach ($projects as $project) {
                $link = $this->link('create', $parameters + ['projectId'=> $project['id']]);
                $links[] = [$project['name'], $link];
            }

            $this->template->projectLinks = $links;
            $this->template->title = $this->getHttpRequest()->getQuery('title', 'Bez popisu');
        } catch (UserRequiredLoggedInFirstException $e) {
            $backlink = $this->storeRequest();
            $this->flashMessage(
                'Prosíme, nejdříve se přihlašte. Po přihlášení bude aplikace pokračovat ve vytvoření úkolu.',
                'warning'
            );
            $this->redirect('Sign:google', ['backlink' => $backlink]);
        } catch (AccessTokenNotFoundException $e) {
            $backlink = $this->storeRequest();
            $this->flashMessage(
                'Prosíme, nejdříve si přidejte váš Todoist účet. Po přihlášení bude aplikace pokračovat ve vytvoření úkolu.',
                'warning'
            );
            $this->redirect('Sign:todoist', ['backlink' => $backlink]);
        } catch (RuntimeException $e) {
            Debugger::log($e, ILogger::EXCEPTION);
            $this->flashMessage(
                Html::el()
                    ->addText('Při vytváření úkolu došlo k neznámé chybě, zkuste to prosím znovu.')
                    ->addHtml(Html::el('br'))
                    ->addText('Chyba: ' . $e->getMessage()),
                'danger'
            );
            $this->forward('createError', $this->getHttpRequest()->getQuery());
        }
    }


    /**
     * @param string $content
     * @param null|int $projectId
     * @return int
     * @throws \App\Model\AccessTokenNotFoundException
     * @throws \App\Model\ApiOperationException
     * @throws \App\Model\UserRequiredLoggedInFirstException
     * @throws \Nette\Utils\JsonException
     * @throws RuntimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function createTask(string $content, ?int $projectId = null): int
    {
        $todoist = $this->todoistClientFactory->create();
        $response = $todoist->createTask($content, $projectId);

        return $response['id'];
    }

}
