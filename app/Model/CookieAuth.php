<?php
declare(strict_types=1);

namespace App\Model;

use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Utils\Random;

class CookieAuth
{
    private const COOKIE_NAME = 'USERID';
    private const ID_LENGTH = '32';
    private const ID_CHARLIST = '0-9a-z';

    public const NO_FLAGS = 0x0;
    public const AUTO_REGISTER = 0x1;
    public const THROW_EXCEPTION = 0x2;

    /**
     * @var Request
     */
    private $httpRequest;
    /**
     * @var Response
     */
    private $httpResponse;

    /**
     * @var bool Refresh cookie when read it?
     */
    public $refreshCookie = true;

    /**
     * @var null|string
     */
    private $userId;


    public function __construct(Request $httpRequest, Response $httpResponse)
    {
        $this->httpRequest = $httpRequest;
        $this->httpResponse = $httpResponse;
    }


    /**
     * @param int $flags
     * @return mixed|string
     * @throws InvalidUserIdException
     * @throws UserIdUndefinedException
     * @throws \Nette\InvalidStateException
     */
    public function getId($flags = self::NO_FLAGS)
    {
        if($this->userId !== null) {
            return $this->userId;
        }

        $userId = $this->httpRequest->getCookie(static::COOKIE_NAME);

        if ($userId !== null) {
            $this->validate($userId);
            $this->userId = $userId;

            // Refresh cookie
            if($this->refreshCookie) {
                $this->saveUserId($userId);
            }

            return $userId;
        }

        if ($flags | static::THROW_EXCEPTION) {
            throw new UserIdUndefinedException('UserID not set');
        }

        if ($flags | static::AUTO_REGISTER) {
            $userId = $this->register();
        }

        return $userId;
    }


    /**
     * @return string
     * @throws \Nette\InvalidStateException
     */
    public function register(): string
    {
        $userId = $this->createId();
        $this->userId = $userId;
        $this->saveUserId($userId);
        return $userId;
    }


    /**
     * @throws \Nette\InvalidStateException
     */
    public function removeId(): void
    {
        $this->httpResponse->deleteCookie(static::COOKIE_NAME);
        $this->userId = null;
    }


    /**
     * @param string $userId
     * @throws \Nette\InvalidStateException
     */
    private function saveUserId(string $userId): void
    {
        $this->httpResponse->setCookie(static::COOKIE_NAME, $userId, '+1 month');
    }


    /**
     * @return string
     */
    private function createId(): string
    {
        return Random::generate(static::ID_LENGTH, static::ID_CHARLIST);
    }


    /**
     * @param $userId
     * @throws InvalidUserIdException
     */
    private function validate($userId): void
    {
        $mask = sprintf('/^[%s]{%d}$/D', static::ID_CHARLIST, static::ID_LENGTH);
        if (!preg_match($mask, $userId)) {
            throw new InvalidUserIdException(sprintf('UserID "%s" has invalid format, must match this mask: %s', $userId, $mask));
        }
    }
}
