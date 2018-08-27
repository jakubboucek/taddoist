<?php
declare(strict_types=1);

namespace App\Model;

use Google\Cloud\Datastore\DatastoreClient;

class DatastoreFactory
{
    /**
     * @var string The full path to your service account credentials .json file retrieved from the Google Developers Console.
     */
    private $googleCredentialsFilename;


    public function __construct(string $googleCredentialsFilename)
    {
        $this->googleCredentialsFilename = $googleCredentialsFilename;
    }


    /**
     * @param string $namespace Partitions data under a namespace. Useful for Multitenant Projects.
     * @return DatastoreClient
     */
    public function create(?string $namespace = null): DatastoreClient
    {
        $config = [
            'keyFilePath' => $this->googleCredentialsFilename,
        ];

        if($namespace) {
            $config['namespaceId'] = $namespace;
        }

        return new DatastoreClient($config);
    }
}
