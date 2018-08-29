<?php
declare(strict_types=1);

namespace App\Model\Todoist;

use App\Model\ApiOperationFailed;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;

class Client
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $client;


    public function __construct(string $accessToken)
    {
        $config = [
            'headers' => [
                'Accept-Encoding' => 'gzip',
                'Authorization' => sprintf('Bearer %s', $accessToken)
            ],
            'base_uri' => 'https://beta.todoist.com/API/v8/',
            'http_errors' => false,
            'timeout' => 5
        ];

        $this->client = new \GuzzleHttp\Client($config);
    }


    /**
     * @param string $content
     * @param null|string $projectId
     * @return array
     * @throws ApiOperationFailed
     * @throws \Nette\Utils\JsonException
     * @throws \RuntimeException
     */
    public function createTask(string $content, ?string $projectId = null): array
    {
        $options = [
            'content' => $content,
        ];
        if ($projectId) {
            $options['project_id'] = (int)$projectId;
        }

        return $this->requestJsonPost('tasks', $options);
    }


    /**
     * @return array
     * @throws \Nette\Utils\JsonException
     * @throws \RuntimeException
     */
    public function findProjects(): array
    {
        return $this->requestGet('projects');
    }


    /**
     * @param string $uri
     * @return array
     * @throws \Nette\Utils\JsonException
     * @throws \RuntimeException
     */
    private function requestGet(string $uri): array
    {
        $options['headers']['X-Request-Id'] = $this->generateV4GUID();
        $result = $this->client->get($uri, $options);
        $status = $result->getStatusCode();
        if ($status !== 200) {
            $content = $result->getBody()->getContents();
            throw new ApiOperationFailed("Server returns status: $status ($content)");
        }
        return Json::decode($result->getBody()->getContents(), Json::FORCE_ARRAY);
    }


    /**
     * @param string $uri
     * @param array $options
     * @return array
     * @throws ApiOperationFailed
     * @throws \Nette\Utils\JsonException
     * @throws \RuntimeException
     */
    private function requestJsonPost(string $uri, array $options): array
    {
        $options['headers']['X-Request-Id'] = $this->generateV4GUID();
        $data = [RequestOptions::JSON => $options];
        $result = $this->client->post($uri, $data);
        $status = $result->getStatusCode();
        if ($status !== 200) {
            $content = $result->getBody()->getContents();
            throw new ApiOperationFailed("Server returns status: $status ($content)");
        }
        return Json::decode($result->getBody()->getContents(), Json::FORCE_ARRAY);
    }


    /**
     * @return string
     */
    private function generateV4GUID(): string
    {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = \chr(\ord($data[6]) & 0x0f | 0x40);
        $data[8] = \chr(\ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

}
