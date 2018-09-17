<?php
declare(strict_types=1);

namespace App\Model\Todoist;

use App\Model\ApiForbiddenException;
use App\Model\ApiOperationException;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;
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
     * @throws ApiOperationException
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
     * @throws ApiForbiddenException
     * @throws ApiOperationException
     * @throws \Nette\Utils\JsonException
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
     */
    private function generateV4GUID(): string
    {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = \chr(\ord($data[6]) & 0x0f | 0x40);
        $data[8] = \chr(\ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }


    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @param array|null $data
     * @return mixed
     * @throws ApiForbiddenException
     * @throws ApiOperationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Nette\Utils\JsonException
     * @throws \RuntimeException
     */
    protected function request(string $method, string $uri, array $data = null, array $options = [])
    {
        $options['headers']['X-Request-Id'] = $this->generateV4GUID();
        if($data) {
            $options[RequestOptions::JSON] = $data;
        }

        $result = $this->client->request($method, $uri, $options);
        $content = $result->getBody()->getContents();
        $status = $result->getStatusCode();
        if ($status !== 200) {
            $message = "Server '$uri' returns status: $status ($content)";
            Debugger::log($message, ILogger::WARNING);
            if ($status === 403) {
                throw new ApiForbiddenException($message, $status);
            }

            throw new ApiOperationException($message, $status);
        }
        return Json::decode($content, Json::FORCE_ARRAY);
    }

}
