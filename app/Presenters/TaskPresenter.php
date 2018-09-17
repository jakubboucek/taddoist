<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Model\AccessTokenNotFoundException;
use App\Model\Todoist;
use App\Model\UserRequiredLoggedInFirstException;
use Nette\Application\UI\Presenter;


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
     * @throws \App\Model\ApiOperationException
     * @throws \Nette\Utils\JsonException
     * @throws \RuntimeException
     */
    public function actionCreate(?string $href = null, ?string $title = null, ?string $projectId = null): void
    {
        if($href === null) {
            $this->flashMessage('Vložený odkaz je neplatný', 'danger');
            $this->redirect('Site:');
        }

        /** @noinspection IsEmptyFunctionUsageInspection */
        $content = !empty($title) ? sprintf('[%s](%s)', $title, $href) : $href;

        try {
            $id = $this->createTask($content, $projectId);
            $url = "https://todoist.com/showTask?id=$id";
            $this->redirectUrl($url);
        } catch (UserRequiredLoggedInFirstException $e) {
            $backlink = $this->storeRequest();
            $this->redirect('Sign:google', ['backlink' => $backlink]);
        } catch (AccessTokenNotFoundException $e) {
            $backlink = $this->storeRequest();
            $this->redirect('Sign:todoist', ['backlink' => $backlink]);
        }

    }


    /**
     * @param string $content
     * @param null|string $projectId
     * @return int
     * @throws \App\Model\AccessTokenNotFoundException
     * @throws \App\Model\ApiOperationException
     * @throws \App\Model\UserRequiredLoggedInFirstException
     * @throws \Nette\Utils\JsonException
     * @throws \RuntimeException
     */
    private function createTask(string $content, ?string $projectId = null): int
    {
        $todoist = $this->todoistClientFactory->create();
        $response = $todoist->createTask($content, $projectId);
        bdump($response);

        return $response['id'];
    }

}
