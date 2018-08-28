<?php
declare(strict_types=1);

namespace App\Presenters;

use App\Model\CookieAuth;
use App\Model\DatastoreFactory;
use App\Model\Google;
use App\Model\Todoist;
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
     * @var Todoist\Authenticator
     */
    private $todoistAuthenticator;
    /**
     * @var CookieAuth
     */
    private $cookieAuth;
    /**
     * @var DatastoreFactory
     */
    private $datastoreFactory;
    /**
     * @var Google\Authenticator
     */
    private $googleAuthenticator;


    /**
     * @param Todoist\Authenticator $todoist
     * @param Google\Authenticator $google
     * @param CookieAuth $cookie
     * @param DatastoreFactory $datastore
     */
    public function __construct(
        Todoist\Authenticator $todoist,
        Google\Authenticator $google,
        CookieAuth $cookie,
        DatastoreFactory $datastore
    ) {
        $this->todoistAuthenticator = $todoist;
        $this->googleAuthenticator = $google;
        $this->cookieAuth = $cookie;
        $this->datastoreFactory = $datastore;
        parent::__construct();
    }


    public function actionOut()
    {
        $this->user->logout(true);

        $this->flashMessage('Byli jste odhlášeni', 'success');

        $this->redirect('Homepage:');
    }


    /**
     * @throws \Nette\Application\UI\InvalidLinkException
     * @throws \Nette\Utils\JsonException
     */
    public function actionTodoist(): void
    {
        $token = $this->createCsrfToken();

        $redirect_url = $this->link('//Sign:actionTodoistCallback');
        $url = $this->todoistAuthenticator->getLoginUrl($token, ['backlink' => $this->backlink], $redirect_url);
        $this->redirectUrl($url);
    }


    /**
     * @param null|string $state
     * @param null|string $code
     * @param null|string $error
     * @return void
     * @throws \App\Model\AuthorizationException
     * @throws \Nette\Application\BadRequestException
     * @throws \Nette\InvalidStateException
     * @throws \RuntimeException
     */
    public function actionTodoistCallback(?string $state = null, ?string $code = null, ?string $error = null): void
    {
        if (\is_string($error)) {
            $this->handleTodoistError($error);
        }

        if (!\is_string($state) || !\is_string($code)) {
            $this->error('Invalid response – State and Code must be presented', IResponse::S400_BAD_REQUEST);
        }

        $token = $this->getCsrfToken();
        $auth = $this->todoistAuthenticator->getAccessToken($token, $code, $state);

        if (isset($auth->getStateData()['backlink'])) {
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

        $this->flashMessage('Aplikace Taddoist je nyní připojena na Váš Todoist', 'success');

        $this->restoreRequest($this->backlink);
        $this->redirect('Homepage:');
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
     * @throws \Nette\Application\UI\InvalidLinkException
     * @throws \Nette\Utils\JsonException
     */
    public function actionGoogle(): void
    {
        $token = $this->createCsrfToken();

        $redirect_url = $this->link('//Sign:googleCallback');
        $url = $this->googleAuthenticator->getLoginUrl($token, $redirect_url, ['backlink' => $this->backlink]);
        $this->redirectUrl($url);
    }


    /**
     * @param null|string $state
     * @param null|string $code
     * @param null|string $error
     * @throws \App\Model\AuthorizationException
     * @throws \Nette\Security\AuthenticationException
     * @throws \Nette\Application\BadRequestException
     * @throws \RuntimeException
     */
    public function actionGoogleCallback(?string $state = null, ?string $code = null, ?string $error = null): void
    {
        if (\is_string($error)) {
            $this->handleGoogleError($error);
        }

        if (!\is_string($state) || !\is_string($code)) {
            $this->error('Invalid response – State and Code must be presented', IResponse::S400_BAD_REQUEST);
        }
        $token = $this->getCsrfToken();
        $authorization = $this->googleAuthenticator->getAccessToken($token, $code, $state);

        $this->user->login($authorization);

        $this->removeCsrfToken();

        $this->flashMessage(sprintf('Nyní jste přihlášeni pod e-mailem: %s, toto přihlášení by mělo zůstat aktivní 30 dní.', $this->user->id), 'success');

        $this->restoreRequest($this->backlink);
        $this->redirect('Homepage:');
    }


    /**
     * @param string $error
     * @throws \Nette\Application\BadRequestException
     * @throws \RuntimeException
     */
    private function handleGoogleError(string $error): void
    {
        if ($error === 'access_denied') {
            $this->error('User Rejected Authorization Request', IResponse::S403_FORBIDDEN);
        } else {
            throw new \RuntimeException(sprintf('Google API returned error: "%s"', $error));
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


    /**
     * @return mixed
     */
    private function getCsrfToken()
    {
        return $this->getHttpRequest()->getCookie(static::CSRF_TOKEN_COOKIE);
    }


    /**
     *
     */
    private function removeCsrfToken(): void
    {
        $this->getHttpResponse()->deleteCookie(static::CSRF_TOKEN_COOKIE);
    }
}
