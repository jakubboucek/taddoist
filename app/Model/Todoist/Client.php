<?php
declare(strict_types=1);

namespace App\Model\Todoist;

use App\Model\ApiForbiddenException;
use App\Model\ApiOperationException;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;
use Ramsey\Uuid\Uuid;
use Tracy\Debugger;
use Tracy\ILogger;

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
            'base_uri' => 'https://api.todoist.com/rest/v1/',
            'http_errors' => false,
            'timeout' => 5
        ];

        $this->client = new \GuzzleHttp\Client($config);
    }


    /**
     * @param string $content
     * @param null|int $projectId
     * @return array
     * @throws ApiForbiddenException
     * @throws ApiOperationException
     * @throws \Nette\Utils\JsonException
     * @throws \RuntimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createTask(string $content, ?int $projectId = null): array
    {
        $options = [
            'content' => $content,
        ];
        if ($projectId) {
            $options['project_id'] = $projectId;
        }

        return $this->requestJsonPost('tasks', $options);
    }


    /**
     * @return array
     * @throws ApiForbiddenException
     * @throws ApiOperationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Nette\Utils\JsonException
     * @throws \Ramsey\Uuid\Exception\UnsatisfiedDependencyException
     * @throws \RuntimeException
     */
    public function findProjects(): array
    {
        return $this->requestGet('projects');
    }


    /**
     * @param string $uri
     * @return array
     * @throws ApiForbiddenException
     * @throws ApiOperationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Nette\Utils\JsonException
     * @throws \Ramsey\Uuid\Exception\UnsatisfiedDependencyException
     * @throws \RuntimeException
     */
    private function requestGet(string $uri): array
    {
        return $this->request('get', $uri);
    }


    /**
     * @param string $uri
     * @param array $data
     * @return array
     * @throws ApiForbiddenException
     * @throws ApiOperationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Nette\Utils\JsonException
     * @throws \RuntimeException
     */
    private function requestJsonPost(string $uri, array $data): array
    {
        return $this->request('post', $uri, $data);
    }


    /**
     * @return string
     * @throws \Ramsey\Uuid\Exception\UnsatisfiedDependencyException
     */
    private function generateV4GUID(): string
    {
        return Uuid::uuid4()->toString();
    }


    /**
     * @param string $method
     * @param string $uri
     * @param array|null $data
     * @param array $options
     * @return mixed
     * @throws ApiForbiddenException
     * @throws ApiOperationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Nette\Utils\JsonException
     * @throws \Ramsey\Uuid\Exception\UnsatisfiedDependencyException
     * @throws \RuntimeException
     */
    protected function request(string $method, string $uri, array $data = null, array $options = [])
    {
        $options['headers']['X-Request-Id'] = $this->generateV4GUID();
        if ($data) {
            $options[RequestOptions::JSON] = $data;
        }

        $result = $this->client->request($method, $uri, $options);
        $content = $result->getBody()->getContents();
        $status = $result->getStatusCode();
        if ($status !== 200) {
            $message = "Remote API on endpoint '$uri' returns status: $status";
            Debugger::log($message, ILogger::WARNING);
            if ($status === 403) {
                throw (new ApiForbiddenException($message, $status))->setContent($content);
            }

            throw (new ApiOperationException($message, $status))->setContent($content);
        }
        return Json::decode($content, Json::FORCE_ARRAY);
    }
}
