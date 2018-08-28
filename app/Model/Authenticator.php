<?php
declare(strict_types=1);

namespace App\Model;

use App\Model\Google\AuthorizationResponse;
use InvalidArgumentException;
use Nette\Security as NS;

class Authenticator implements NS\IAuthenticator
{
    public function authenticate(array $credentials): NS\IIdentity
    {
        [$authentication] = $credentials;
        if(!$authentication instanceof AuthorizationResponse) {
            throw new InvalidArgumentException('Only allowed authenticator is class: ' . AuthorizationResponse::class);
        }

        return new NS\Identity($authentication->getIdEmail());
    }
}
