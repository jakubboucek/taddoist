<?php

declare(strict_types=1);

namespace App\Model;

use Google\Cloud\Datastore\DatastoreClient;
use Google\Cloud\Datastore\Key;
use Nette\Security\User;
use Nette\Utils\DateTime;

class UserStorage
{
    private const NAMESPACE = 'UserSettings';

    /** @var DatastoreClient */
    private $datastore;

    /** @var User */
    private $user;

    public function __construct(User $user, DatastoreFactory $datastoreFactory)
    {
        $this->user = $user;
        $this->datastore = $datastoreFactory->create(static::NAMESPACE);
    }

    public function get(string $key, $default = null)
    {
        $entity = $this->datastore->lookup($this->getKey($key));
        if ($entity !== null && isset($entity['data'])) {
            return $entity['data'];
        }
        return $default;
    }

    public function set(string $key, $value): void
    {
        $entity = $this->datastore->entity(
            $this->getKey($key),
            [
                'data' => $value,
                'created' => new DateTime(),
            ],
            [
                'excludeFromIndexes' => ['data', 'created']
            ]
        );

        $this->datastore->upsert($entity);
    }

    private function getKey(string $key): Key
    {
        $kind = $this->getUserId();

        return $this->datastore->key($kind, $key);
    }

    private function getUserId()
    {
        if ($this->user->loggedIn !== true) {
            throw new UserRequiredLoggedInFirstException('User is not Logged to access their data');
        }

        return $this->user->id;
    }
}
