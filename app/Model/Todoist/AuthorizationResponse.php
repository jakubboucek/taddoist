<?php
declare(strict_types=1);

namespace App\Model\Todoist;

class AuthorizationResponse
{

    /**
     * @var string
     */
    private $accesToken;
    /**
     * @var array
     */
    private $stateData;


    public function __construct(string $accesToken, array $stateData)
    {

        $this->accesToken = $accesToken;
        $this->stateData = $stateData;
    }


    /**
     * @return string
     */
    public function getAccesToken(): string
    {
        return $this->accesToken;
    }


    /**
     * @return array
     */
    public function getStateData(): array
    {
        return $this->stateData;
    }
}
