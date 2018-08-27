<?php
declare(strict_types=1);

namespace App\Model\Todoist;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use RuntimeException;

class Authorizator
{
    public const API_AUTH_URL = 'https://todoist.com/oauth/authorize';
    public const API_EXCHANGE_URL = 'https://todoist.com/oauth/access_token';

    public const SCOPE = 'task:add,data:read';

    /**
     * @var string
     */
    private $clientId;
    /**
     * @var string
     */
    private $clientSecret;


    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }


    /**
     * Step 1. - redirect user to API for grant permissions
     * @param string $csrfToken Generated CSRF token
     * @param array $stateData Data to
     * @return string
     * @throws \Nette\Utils\JsonException
     */
    public function getLoginUrl(string $csrfToken, array $stateData = [], ?string $redirect_uri = null): string
    {
        $state = [
            'csrf' => $csrfToken,
            'data' => $stateData,
        ];

        $params = [
            'client_id' => $this->clientId,
            'scope' => static::SCOPE,
            'state' => $this->urlsafeB64Encode(Json::encode($state)),
        ];

        if ($redirect_uri !== null) {
            $params['redirect_uri'] = $redirect_uri;
        }

        return sprintf('%s?%s', static::API_AUTH_URL, http_build_query($params));
    }


    /**
     * @param string $input
     * @return string
     */
    private function urlsafeB64Encode(string $input): string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }


    /**
     * Step 2. - process params returned from API
     * @param string $csrfToken Generated CSRF token for validation (must be same as in `getLoginUrl()`)
     * @param string $urlCode Code parametr from URL
     * @param string $urlState State parametr from URL
     * @return AuthorizationResponse
     * @throws AuthorizationException
     * @throws \RuntimeException
     */
    public function getAccessToken(string $csrfToken, string $urlCode, string $urlState): AuthorizationResponse
    {
        $state = $this->decodeState($urlState, $csrfToken);
        $accessToken = $this->exchangeCode($urlCode);

        return new AuthorizationResponse($accessToken, $state);
    }


    /**
     * @param string $serializedState Encoded state from URL
     * @param string $csrfToken CSRF token for validation
     * @return array
     * @throws AuthorizationException
     */
    private function decodeState(string $serializedState, string $csrfToken): array
    {
        try {
            $state = Json::decode($this->urlsafeB64Decode($serializedState), Json::FORCE_ARRAY);

            if (!isset($state['csrf']) || $state['csrf'] !== $csrfToken) {
                $csrf = $state['csrf'] ?? 'parameter undefined';
                throw new CsrfProtectionFailedException(sprintf('CRSF token mismatched ("%s" vs "%s")', $csrf,
                    $csrfToken));
            }

            if (!isset($state['data']) || !\is_array($state['data'])) {
                $msg = isset($state['data']) ? \gettype($state['data']) : 'undefined';
                throw new InvalidStateException(sprintf('State\'s parametr "data" should be type array, %s instead.',
                    $msg));
            }

            return $state['data'];
        } catch (JsonException $e) {
            throw new InvalidStateException('Unable to decode State parameter', $e->getCode(), $e);
        }
    }


    /**
     * @param string $input
     * @return string
     */
    private function urlsafeB64Decode(string $input): string
    {
        $remainder = \strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'), true);
    }


    /**
     * @param string $code
     * @return string
     * @throws AuthorizationException
     */
    private function exchangeCode(string $code): string
    {
        $request = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code
        ];

        $client = new Client();

        try {
            $response = $client->post(static::API_EXCHANGE_URL, [
                'form_params' => $request,
            ]);

            $array = Json::decode($response->getBody()->getContents(), Json::FORCE_ARRAY);

            if (!isset($array['access_token'])) {
                throw new TokenExchangeException('Response does not contain "access_token" field');
            }

            return $array['access_token'];
        } catch (GuzzleException $e) {
            throw new TokenExchangeException('Error during call API', $e->getCode(), $e);
        } catch (JsonException $e) {
            throw new TokenExchangeException('API response is invalid JSON', $e->getCode(), $e);
        } catch (RuntimeException $e) {
            throw new TokenExchangeException('Unable to get API response', $e->getCode(), $e);
        }
    }
}
