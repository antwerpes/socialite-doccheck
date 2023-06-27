<?php declare(strict_types=1);

namespace Antwerpes\SocialiteDocCheck;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class DocCheckSocialiteProvider extends AbstractProvider implements ProviderInterface
{
    protected array $config = [];

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Get the access token response for the given code.
     *
     * @param string $code
     *
     * @throws GuzzleException
     */
    public function getAccessTokenResponse($code): array
    {
        $url = $this->getTokenUrl().'?'.http_build_query($this->getTokenFields($code), '', '&', $this->encodingType);
        $response = $this->getHttpClient()->get($url);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritDoc}
     */
    protected function getAuthUrl($state): string
    {
        $language = $this->config['language'] === 'en' ? 'com' : $this->config['language'];

        return 'https://login.doccheck.com/code/?'.http_build_query([
            'dc_language' => $language,
            'dc_client_id' => $this->clientId,
            'dc_template' => $this->config['template'],
            'state' => $state,
            'redirect_uri' => $this->redirectUrl,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://login.doccheck.com/service/oauth/access_token';
    }

    /**
     * {@inheritDoc}
     */
    protected function getUserByToken($token): array
    {
        if ($this->config['license'] === 'economy') {
            return [
                'uniquekey' => $this->request->input('login_id'),
            ];
        }

        try {
            $response = $this->getHttpClient()->get(
                'https://login.doccheck.com/service/oauth/user_data',
                [
                    RequestOptions::HEADERS => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer '.$token,
                    ],
                ],
            );
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 400 && ($body['error'] ?? null) === 'revoked_token') {
                return ['uniquekey' => $body['uniquekey']];
            }

            throw $exception;
        }

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritDoc}
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['uniquekey'],
            'email' => $user['email'] ?? null,
            'title' => $this->decodeValue($user['address_name_title'] ?? null),
            'first_name' => $this->decodeValue($user['address_name_first'] ?? null),
            'last_name' => $this->decodeValue($user['address_name_last'] ?? null),
            'street' => $this->decodeValue($user['address_street'] ?? null),
            'postal_code' => $user['address_postal_code'] ?? null,
            'city' => $this->decodeValue($user['address_city'] ?? null),
            'country' => $user['address_country_iso'] ?? null,
            'date_of_birth' => $user['date_of_birth'] ?? null,
            'language' => $user['language_iso'] ?? null,
            'gender' => $user['address_gender'] ?? null,
            'profession_id' => $this->getRelatedId($user['occupation_profession_id'] ?? null),
            'discipline_id' => $this->getRelatedId($user['occupation_discipline_id'] ?? null),
        ]);
    }

    /**
     * Decode html-encoded entities from user data response.
     *
     * @param mixed $value
     */
    protected function decodeValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return html_entity_decode($value);
    }

    protected function getRelatedId($value): ?int
    {
        if ($value === null || $value === '0' || $value === 0) {
            return null;
        }

        return (int) $value;
    }
}
