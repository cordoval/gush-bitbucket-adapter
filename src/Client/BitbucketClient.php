<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter\Client;

use Bitbucket\API\Authentication\Basic;
use Bitbucket\API\Authentication\OAuth;
use Bitbucket\API\Http\Listener\OAuthListener;
use Bitbucket\API\Http\Listener\BasicAuthListener;
use Bitbucket\API\User;

/**
 * @author Raul Rodriguez <raulrodriguez782@gmail.com>
 */
abstract class BitbucketClient
{
    protected $user = null;

    protected $credentials = array();

    const AUTH_HTTP_PASSWORD = 'http_password';
    const AUTH_HTTP_TOKEN = 'http_token';

    /**
     * @var array
     */
    protected $config;

    public function __construct(array $config)
    {
        $this->user = new User();
        $this->config = $config;
    }

    public function setCredentials($credentials)
    {
        $this->credentials = $credentials;
    }

    public function authenticate()
    {
        $auth = $this->getAuth();
        $this->addAuthListener($auth, $this->user);
        $response = $this->user->get();

        return $response->isSuccessful();
    }

    /**
     * @param $fqnClass
     *
     * @return \Bitbucket\API\Api
     */
    protected function api($fqnClass)
    {
        $api = new $fqnClass();
        /** @var \Bitbucket\API\Api $api */
        $api->setCredentials($this->getCredentials());
        $auth = $this->getAuth();
        $this->addAuthListener($auth, $api);

        return $api;
    }

    protected function getAuth()
    {
        return (isset($this->credentials['secret'])) ? self::AUTH_HTTP_TOKEN : self::AUTH_HTTP_PASSWORD;
    }

    protected function addAuthListener($auth, $api)
    {
        if ($auth == self::AUTH_HTTP_PASSWORD) {
            $listener = new BasicAuthListener(
                $this->credentials['username'],
                $this->credentials['password-or-token']
            );
        } else {
            $listener = new OAuthListener(
                [
                    'oauth_consumer_key' => $this->credentials['password-or-token'],
                    'oauth_consumer_secret' => $this->credentials['secret']
                ]
            );
        }

        $api->getClient()->addListener($listener);
    }

    protected function getCredentials()
    {
        if (isset($this->credentials['secret'])) {
            return new OAuth(
                [
                    'oauth_consumer_key' => $this->credentials['password-or-token'],
                    'oauth_consumer_secret' => $this->credentials['secret']
                ]
            );
        }

        return new Basic($this->credentials['username'], $this->credentials['password-or-token']);
    }

    protected function prepareParameters(array $parameters)
    {
        foreach ($parameters as $k => $v) {
            if (null === $v) {
                unset($parameters[$k]);
            }
        }

        return $parameters;
    }
}
