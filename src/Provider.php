<?php

namespace SocialiteProviders\Agave;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'AGAVE';
    /**
     * {@inheritdoc}
     */
    protected $scopes = ['PRODUCTION'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getInstanceUri() . 'authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getInstanceUri() . 'token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getInstanceUri() . 'profiles/v2/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        \Log::debug($user);
        $user = isset($user['result']) ? $user['result'] : $user;
        $snakeCaseUserArray = [];
        foreach ($user as $key => $value) {
            $snakeCaseUserArray[snake_case($key)] = $value;
        }
        return (new User())->setRaw($user)->map([
            'id' => array_get($user, 'username'),
            'username' => array_get($user, 'username'),
            'nickname' => array_get($user, 'username'),
            'name' => $this->getUserFullName($user),
            'email' => array_get($user, 'email'),
            'status' => array_get($user, 'status'),
            'phone' => array_get($user, 'phone'),
            'mobile_phone' => array_get($user, 'mobile_phone'),
            'avatar' => array_get($user, 'avatar_url'),
        ]);
    }

    /**
     * Gets full name from user array. If no `full_name` key is present, uses the
     * first_name and last_name keys instead.
     *
     * @param array $user
     * @return mixed|string
     */
    protected function getUserFullName(array $user)
    {
        $fullName = array_get($user, 'full_name');
        if (empty($fullName)) {
            $firstName = array_get($user, 'first_name', '');
            $lastName = array_get($user, 'last_name', '');
            $fullName = "{$firstName} {$lastName}";
        }
        return trim($fullName);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        $tokenFields = array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
            'redirect_uri' => (starts_with($this->redirectUrl, "/") ? url($this->redirectUrl) : $this->redirectUrl),
        ]);
        \Log::debug($tokenFields);
        return $tokenFields;
    }

    /**
     * {@inheritdoc}
     */
    protected function getInstanceUri()
    {
        $instanceUrl = $this->getConfig('instance_uri', 'https://public.agaveapi.co/');
        return (rtrim($instanceUrl, '/\s')) . '/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id' => $this->clientId,
            'redirect_uri' => (starts_with($this->redirectUrl, "/") ? url($this->redirectUrl) : $this->redirectUrl),
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type' => 'code',
        ];
        if ($this->usesState()) {
            $fields['state'] = $state;
        }
        \Log::debug(array_merge($fields, $this->parameters));
        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function redirectUrl($url)
    {
        $this->redirectUrl = starts_with($url, "/") ? url($url) : $url;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['instance_uri'];
    }
}