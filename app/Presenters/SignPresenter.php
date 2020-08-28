<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\Google;
use App\Model\Todoist;
use App\Model\UserStorage;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Security\AuthenticationException;
use Nette\Utils\JsonException;
use Nette\Utils\Random;
use RuntimeException;

class SignPresenter extends Presenter
{
    use LayoutTrait;

    private const CSRF_TOKEN_COOKIE = 'taddoist-sign-csrf';

    /**
     * @var null|string
     * @persistent
     */
    public $backlink;

    /** @var Todoist\Authenticator */
    private $todoistAuthenticator;

    /** @var Google\Authenticator */
    private $googleAuthenticator;

    /** @var UserStorage */
    private $userStorage;

    public function __construct(
        Todoist\Authenticator $todoist,
        Google\Authenticator $google,
        UserStorage $userStorage
    ) {
        $this->todoistAuthenticator = $todoist;
        $this->googleAuthenticator = $google;
        $this->userStorage = $userStorage;
        parent::__construct();
    }

    public function actionOut(): void
    {
        $this->user->logout(true);

        $this->flashMessage('Byli jste odhlášeni', 'success');

        $this->redirect('Site:');
    }

    /**
     * @throws InvalidLinkException
     * @throws JsonException
     */
    public function actionTodoistGo(): void
    {
        if ($this->user->loggedIn !== true) {
            $backlink = $this->storeRequest();
            $this->redirect('google', ['backlink' => $backlink]);
        }

        $token = $this->createCsrfToken();

        $redirect_url = $this->link('//Sign:todoistCallback', ['backlink' => null]);
        $url = $this->todoistAuthenticator->getLoginUrl($token, ['backlink' => $this->backlink], $redirect_url);
        $this->redirectUrl($url);
    }

    /**
     * @param string|null $state
     * @param string|null $code
     * @param string|null $error
     * @throws BadRequestException
     */
    public function actionTodoistCallback(?string $state = null, ?string $code = null, ?string $error = null): void
    {
        if (is_string($error)) {
            $this->handleTodoistError($error);
        }

        if (!is_string($state) || !is_string($code)) {
            $this->error('Invalid response – State and Code must be presented', IResponse::S400_BAD_REQUEST);
        }

        $token = $this->getCsrfToken();
        $auth = $this->todoistAuthenticator->getAccessToken($token, $code, $state);

        if (isset($auth->getStateData()['backlink'])) {
            $this->backlink = $auth->getStateData()['backlink'];
        }

        $this->userStorage->set('todoist.access_token', $auth->getAccesToken());

        //clean
        $this->removeCsrfToken();

        $this->flashMessage('Aplikace Taddoist je nyní připojena na Váš Todoist', 'success');

        $this->restoreRequest($this->backlink);
        $this->redirect('Site:');
    }

    /**
     * @param string $error
     * @throws BadRequestException
     */
    private function handleTodoistError(string $error): void
    {
        if ($error === 'access_denied') {
            $this->error('User Rejected Authorization Request', IResponse::S403_FORBIDDEN);
        } else {
            throw new RuntimeException(sprintf('Todoist API returned error: "%s"', $error));
        }
    }

    /**
     * @throws InvalidLinkException
     * @throws JsonException
     */
    public function actionGoogleGo(): void
    {
        $token = $this->createCsrfToken();

        $redirect_url = $this->link('//Sign:googleCallback', ['backlink' => null]);
        $url = $this->googleAuthenticator->getLoginUrl($token, $redirect_url, ['backlink' => $this->backlink]);
        $this->redirectUrl($url);
    }

    /**
     * @param string|null $state
     * @param string|null $code
     * @param string|null $error
     * @throws BadRequestException
     * @throws AuthenticationException
     * @noinspection PhpUnused
     */
    public function actionGoogleCallback(?string $state = null, ?string $code = null, ?string $error = null): void
    {
        if (is_string($error)) {
            $this->handleGoogleError($error);
        }

        if (!is_string($state) || !is_string($code)) {
            $this->error('Invalid response – State and Code must be presented', IResponse::S400_BAD_REQUEST);
        }
        $token = $this->getCsrfToken();
        $authorization = $this->googleAuthenticator->getAccessToken($token, $code, $state);

        $this->user->login($authorization);

        $this->removeCsrfToken();

        $this->flashMessage(
            sprintf(
                'Nyní jste přihlášeni pod e-mailem: %s, toto přihlášení by mělo zůstat aktivní 30 dní.',
                $this->user->id
            ),
            'success'
        );

        $this->restoreRequest($this->backlink);
        $this->redirect('Site:');
    }

    /**
     * @param string $error
     * @throws BadRequestException
     * @throws RuntimeException
     */
    private function handleGoogleError(string $error): void
    {
        if ($error === 'access_denied') {
            $this->error('User Rejected Authorization Request', IResponse::S403_FORBIDDEN);
        } else {
            throw new RuntimeException(sprintf('Google API returned error: "%s"', $error));
        }
    }

    private function createCsrfToken(): string
    {
        $token = Random::generate();
        $this->getHttpResponse()->setCookie(static::CSRF_TOKEN_COOKIE, $token, '+10 minutes');
        return $token;
    }

    private function getCsrfToken(): ?string
    {
        return $this->getHttpRequest()->getCookie(static::CSRF_TOKEN_COOKIE);
    }

    private function removeCsrfToken(): void
    {
        $this->getHttpResponse()->deleteCookie(static::CSRF_TOKEN_COOKIE);
    }
}
