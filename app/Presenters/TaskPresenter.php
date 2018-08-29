<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Model\Todoist;
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


    public function actionCreate(?string $href = null, ?string $title = null, ?string $projectId = null)
    {
        if($href === null) {
            $this->flashMessage('Vložený odkaz je neplatný', 'danger');
            $this->redirect('Site:');
        }

        /** @noinspection IsEmptyFunctionUsageInspection */
        $content = !empty($title) ? sprintf('[%s](%s)', $title, $href) : $href;

        $id = $this->createTask($content, $projectId);

        $url = "https://todoist.com/showTask?id=$id";

        $this->redirectUrl($url);
    }


    /**
     * @param string $content
     * @param null|string $projectId
     * @return int
     * @throws \App\Model\AccessTokenNotFoundException
     * @throws \App\Model\ApiOperationFailed
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
