<?php
declare(strict_types=1);

namespace App\Model\Google;

use Google_Client as Client;

class GoogleClientFactory
{


    /**
     * @var string
     */
    private $googleCredentialsFilename;


    /**
     * @param string $googleCredentialsFilename
     */
    public function __construct(string $googleCredentialsFilename)
    {
        $this->googleCredentialsFilename = $googleCredentialsFilename;
    }


    /**
     * @param array $config
     * @return Client
     * @throws \Google_Exception
     */
    public function create(array $config = []): Client
    {
        $client = new Client($config);

        // If no config added directly, put default config
        if(\count($config) === 0) {
            $client->setAuthConfig($this->googleCredentialsFilename);
        }
        return $client;
 }
}
