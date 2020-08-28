<?php

declare(strict_types=1);

namespace App\Model\Todoist;

use App\Model\ApiForbiddenException;
use App\Model\ApiOperationException;
use App\Model\Exception;
use App\Model\Google\AppVersionProvider;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Ramsey\Uuid\Uuid;
use Tracy\Debugger;
use Tracy\ILogger;

class Client
{
    /** @var GuzzleClient */
    private $client;

    public function __construct(string $accessToken, AppVersionProvider $appVersionProvider)
    {
        $config = [
            'headers' => [
                'Accept-Encoding' => 'gzip',
                'Authorization' => sprintf('Bearer %s', $accessToken),
                'User-Agent' => sprintf(
                    'Taddoist %s (https://taddoist.appspot.com)',
                    $appVersionProvider->getVersion()
                ),
            ],
            'base_uri' => 'https://api.todoist.com/rest/v1/',
            'http_errors' => false,
            'timeout' => 5
        ];

        $this->client = new GuzzleClient($config);
    }

    /**
     * @param string $content
     * @param int|null $projectId
     * @return array
     * @throws ApiForbiddenException
     * @throws ApiOperationException
     * @throws JsonException
     * @throws GuzzleException
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
     * @throws GuzzleException
     * @throws JsonException
     */
    public function findProjects(): array
    {
        return $this->requestGet('projects');
    }

    /**
     * @param string $uri
     * @return array
     * @throws JsonException
     * @throws GuzzleException
     */
    private function requestGet(string $uri): array
    {
        return $this->request('get', $uri);
    }

    /**
     * @param string $uri
     * @param array $data
     * @return array
     * @throws GuzzleException
     * @throws JsonException
     */
    private function requestJsonPost(string $uri, array $data): array
    {
        return $this->request('post', $uri, $data);
    }

    private function generateV4GUID(): string
    {
        try {
            return Uuid::uuid4()->toString();
        } catch (\Exception $e) {
            throw new Exception('Unable to generate UUID: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array|null $data
     * @param array $options
     * @return mixed
     * @throws GuzzleException
     * @throws JsonException
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
