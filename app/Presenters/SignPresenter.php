<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Model\CookieAuth;
use App\Model\DatastoreFactory;
use App\Model\Todoist\Authorizator;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Nette\Utils\Random;

class SignPresenter extends Presenter
{
    private const CSRF_TOKEN_COOKIE = 'taddoist-sign-csrf';

    /**
     * @var null|string
     * @persistent
     */
    public $backlink;
    /**
     * @var Authorizator
     */
    private $todoistAuthorizator;
    /**
     * @var CookieAuth
     */
    private $cookieAuth;
    /**
     * @var DatastoreFactory
     */
    private $datastoreFactory;


    /**
     * @param Authorizator $todoist
     * @param CookieAuth $cookie
     * @param DatastoreFactory $datastore
     */
    public function __construct(Authorizator $todoist, CookieAuth $cookie, DatastoreFactory $datastore)
    {
        $this->todoistAuthorizator = $todoist;
        $this->cookieAuth = $cookie;
        $this->datastoreFactory = $datastore;
        parent::__construct();
    }


    /**
     * @throws \Nette\Utils\JsonException
     */
    public function actionTodoist(): void
    {
        $token = $this->createCsrfToken();

        $redirect_url = $this->link('//Sign:actionTodoistCallback');
        $url = $this->todoistAuthorizator->getLoginUrl($token, ['backlink' => $this->backlink], $redirect_url);
        $this->redirectUrl($url);
    }


    /**
     * @param null|string $state
     * @param null|string $code
     * @param null|string $error
     * @return \App\Model\Todoist\AuthorizationResponse
     * @throws \App\Model\Todoist\AuthorizationException
     * @throws \App\Model\Todoist\TokenExchangeException
     * @throws \Nette\Application\BadRequestException
     * @throws \RuntimeException
     */
    public function actionTodoistCallback(?string $state = null, ?string $code = null, ?string $error = null): \App\Model\Todoist\AuthorizationResponse
    {
        if (\is_string($error)) {
            $this->handleTodoistError($error);
        }

        if (!\is_string($state) || !\is_string($code)) {
            $this->error('Invalid response – State and Code must be presented', IResponse::S400_BAD_REQUEST);
        }

        $token = $this->getCsrfToken();
        $auth = $this->todoistAuthorizator->getAccessToken($token, $code, $state);

        if(isset($auth->getStateData()['backlink'])) {
            $this->backlink = $auth->getStateData()['backlink'];
        }

        $userId = $this->cookieAuth->register();

        $datastore = $this->datastoreFactory->create($userId);

        $entity = $datastore->entity($datastore->key('UserData', 'session'), [
            'access_token' => $auth->getAccesToken(),
            'created' => new DateTime(),
        ]);

        $datastore->insert($entity);
        
        //clean
        $this->removeCsrfToken();

        $this->restoreRequest($this->backlink);
        $this->redirect('Homepage:default');
    }


    /**
     * @param string $error
     * @throws \Nette\Application\BadRequestException
     * @throws \RuntimeException
     */
    private function handleTodoistError(string $error): void
    {
        if ($error === 'access_denied') {
            $this->error('User Rejected Authorization Request', IResponse::S403_FORBIDDEN);
        } else {
            throw new \RuntimeException(sprintf('Todoist API returned error: "%s"', $error));
        }
    }


    /**
     * @return string
     */
    private function createCsrfToken(): string
    {
        $token = Random::generate();
        $this->getHttpResponse()->setCookie(static::CSRF_TOKEN_COOKIE, $token, '+10 minutes');
        return $token;
    }


    private function getCsrfToken()
    {
        return $this->getHttpRequest()->getCookie(static::CSRF_TOKEN_COOKIE);
    }


    private function removeCsrfToken()
    {
        $this->getHttpResponse()->deleteCookie(static::CSRF_TOKEN_COOKIE);
    }
}
