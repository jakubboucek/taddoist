<?php
declare(strict_types=1);

namespace App\Model\Google;

class AuthorizationResponse
{
    /**
     * @var string
     */
    private $accessTokenBundle;
    /**
     * @var array
     */
    private $state;
    /**
     * @var string
     */
    private $idTokenBundle;


    /**
     * @param string $accessTokenBundle
     * @param array $state
     * @param string $idTokenBundle
     */
    public function __construct(array $accessTokenBundle, array $state, array $idTokenBundle)
{
    $this->accessTokenBundle = $accessTokenBundle;
    $this->state = $state;
    $this->idTokenBundle = $idTokenBundle;
}


    /**
     * @return array
     */
    public function getAccessTokenBundle(): array
    {
        return $this->accessTokenBundle;
    }


    public function getAccessToken(): string
    {
        return $this->accessTokenBundle['access_token'];
    }


    /**
     * @return array
     */
    public function getState(): array
    {
        return $this->state;
    }


    /**
     * @return array
     */
    public function getIdTokenBundle(): array
    {
        return $this->idTokenBundle;
    }


    public function getIdEmail(bool $onlyVerified = true): ?string
    {
        if($onlyVerified && $this->idTokenBundle['email_verified'] !== true) {
            return null;
        }

        return $this->idTokenBundle['email'];
    }
}
