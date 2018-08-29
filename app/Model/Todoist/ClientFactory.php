<?php
declare(strict_types=1);

namespace App\Model\Todoist;

use App\Model\AccessTokenNotFoundException;
use App\Model\UserStorage;

class ClientFactory
{
    /**
     * @var UserStorage
     */
    private $userStorage;


    /**
     * @param UserStorage $userStorage
     */
    public function __construct(UserStorage $userStorage)
    {
        $this->userStorage = $userStorage;
    }


    /**
     * @return Client
     * @throws AccessTokenNotFoundException
     * @throws \App\Model\UserRequiredLoggedInFirstException
     */
    public function create(): Client
    {
        $accessToken = $this->userStorage->get('todoist.access_token');

        if($accessToken === null) {
            throw new AccessTokenNotFoundException('Access token not found');
        }

        return new Client((string) $accessToken);
    }
}
