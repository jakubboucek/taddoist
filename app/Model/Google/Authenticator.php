<?php
declare(strict_types=1);

namespace App\Model\Google;

use App\Model\AuthorizationException;
use App\Model\CsrfProtectionFailedException;
use App\Model\Helpers;
use App\Model\InvalidStateException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class Authenticator
{
    /**
     * @var \Google_Client
     */
    private $client;


    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param GoogleClientFactory $googleClientFactory
     * @throws \Google_Exception
     */
    public function __construct(string $clientId, string $clientSecret, GoogleClientFactory $googleClientFactory)
    {
        $this->client = $googleClientFactory->create([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);
    }


    /**
     * @param string $csrfToken
     * @param array $stateData
     * @param null|string $redirect_uri
     * @return string
     * @throws JsonException
     */
    public function getLoginUrl(string $csrfToken, string $redirect_uri, array $stateData = []): string
    {
        $state = [
            'csrf' => $csrfToken,
            'redirect_uri' => $redirect_uri,
            'data' => $stateData,
        ];
        $state = Helpers::urlsafeB64Encode(Json::encode($state));

        $this->client->setScopes(['https://www.googleapis.com/auth/userinfo.email']);
        $this->client->setState($state);
        $this->client->setRedirectUri($redirect_uri);
        return $this->client->createAuthUrl();
    }


    /**
     * @param string $csrfToken
     * @param string $urlCode
     * @param string $urlState
     * @return AuthorizationResponse
     * @throws AuthorizationException
     */
    public function getAccessToken(string $csrfToken, string $urlCode, string $urlState): AuthorizationResponse
    {
        [$state, $redirect_uri] = $this->decodeState($urlState, $csrfToken);

        $this->client->setRedirectUri($redirect_uri);
        $this->client->fetchAccessTokenWithAuthCode($urlCode);
        $accessToken = $this->client->getAccessToken();

        $idToken = $this->client->verifyIdToken();

        return new AuthorizationResponse($accessToken, $state, $idToken);
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
            $state = Json::decode(Helpers::urlsafeB64Decode($serializedState), Json::FORCE_ARRAY);

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

            return [
                $state['data'],
                $state['redirect_uri'] ?? null,
            ];
        } catch (JsonException $e) {
            throw new InvalidStateException('Unable to decode State parameter', $e->getCode(), $e);
        }
    }
}
