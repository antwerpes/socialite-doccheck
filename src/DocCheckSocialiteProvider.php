<?php declare(strict_types=1);

namespace Antwerpes\SocialiteDocCheck;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class DocCheckSocialiteProvider extends AbstractProvider implements ProviderInterface
{
    protected const BASE_URL = 'https://auth.doccheck.com/';
    protected array $config = [];
    protected $scopeSeparator = ' ';
    protected $scopes = ['unique_id', 'profession', 'country', 'language'];

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            static::BASE_URL.$this->getAuthorizationLanguage().'/authorize',
            $state,
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getCodeFields($state = null): array
    {
        return array_merge(parent::getCodeFields($state), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTokenUrl(): string
    {
        return static::BASE_URL.'token';
    }

    /**
     * {@inheritDoc}
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get(
            static::BASE_URL.'api/users/data',
            [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ],
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritDoc}
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['unique_id'] ?? $user['uniquekey'] ?? null,
            'email' => $user['email'] ?? null,
            'first_name' => $this->decodeValue($user['first_name'] ?? $user['address_name_first'] ?? null),
            'last_name' => $this->decodeValue($user['last_name'] ?? $user['address_name_last'] ?? null),
            'street' => $this->decodeValue($user['street'] ?? $user['address_street'] ?? null),
            'postal_code' => $user['area_code'] ?? $user['address_postal_code'] ?? null,
            'city' => $this->decodeValue($user['city'] ?? $user['address_city'] ?? null),
            'country' => $user['country_iso_code'] ?? $user['country'] ?? $user['address_country_iso'] ?? null,
            'date_of_birth' => $user['date_of_birth'] ?? null,
            'language' => $user['user_language'] ?? $user['language'] ?? $user['language_iso'] ?? null,
            'profession_id' => $this->getRelatedId($user['profession_id'] ?? $user['profession'] ?? $user['occupation_profession_id'] ?? null),
            'discipline_id' => $this->getRelatedId($user['discipline_id'] ?? $user['occupation_discipline_id'] ?? null),
            'activity_id' => $this->getRelatedId($user['activity_id'] ?? null),
        ]);
    }

    /**
     * Decode html-encoded entities from user data response.
     */
    protected function decodeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return html_entity_decode($value);
    }

    protected function getRelatedId(mixed $value): ?int
    {
        if ($value === null || $value === '0' || $value === 0) {
            return null;
        }

        return (int) $value;
    }

    protected function getAuthorizationLanguage(): string
    {
        $language = strtolower((string) ($this->config['language'] ?? 'de'));

        return in_array($language, ['de', 'en'], true) ? $language : 'de';
    }
}
