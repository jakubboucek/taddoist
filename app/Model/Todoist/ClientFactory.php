<?php

declare(strict_types=1);

namespace App\Model\Todoist;

use App\Model\AccessTokenNotFoundException;
use App\Model\Google\AppVersionProvider;
use App\Model\UserStorage;

class ClientFactory
{
    /** @var UserStorage */
    private $userStorage;

    /** @var AppVersionProvider */
    private $versionProvider;


    public function __construct(UserStorage $userStorage, AppVersionProvider $versionProvider)
    {
        $this->userStorage = $userStorage;
        $this->versionProvider = $versionProvider;
    }

    public function create(): Client
    {
        $accessToken = $this->userStorage->get('todoist.access_token');

        if ($accessToken === null) {
            throw new AccessTokenNotFoundException('Access token not found');
        }

        return new Client((string)$accessToken, $this->versionProvider);
    }
}
