<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter\Decorator;

use Bitbucket\API\Authentication\Basic;
use Bitbucket\API\Authentication\OAuth;
use Bitbucket\API\Http\Listener\OAuthListener;
use Bitbucket\API\Http\Listener\BasicAuthListener;
use Bitbucket\API\Repositories\Repository;
use Bitbucket\API\User;

/**
 * @author Raul Rodriguez <raulrodriguez782@gmail.com>
 */
class BitbucketClientDecorator
{
    protected $user = null;

    protected $credentials = array();

    const AUTH_HTTP_PASSWORD = 'http_password';
    const AUTH_HTTP_TOKEN = 'http_token';

    public function __construct()
    {
        $this->user = new User();
    }

    public function setCredentials($credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Authenticate Bitbucket
     *
     * @return boolean
     */
    public function authenticate()
    {
        $auth = $this->getAuth();
        $this->addAuthListener($auth, $this->user);
        $response = $this->user->get();

        return $response->isSuccessful();
    }

    public function fork($username, $repository, $org)
    {
        $api = $this->api('Bitbucket\API\Repositories\Repository');

        $api->fork($username, $repository, $org, []);
    }

    public function issues($username, $repository, array $parameters)
    {
        $api = $this->api('Bitbucket\API\Repositories\Issues');
        $auth = $this->getAuth();
        $this->addAuthListener($auth, $api);

        return $api->all($username, $repository, $parameters);
    }

    protected function api($fqnClass)
    {
        $api = new $fqnClass();
        $api->setCredentials($this->getCredentials());

        return $api;
    }

    protected function getAuth()
    {
        return ( isset($this->credentials['secret']) )
            ?self::AUTH_HTTP_TOKEN
            :self::AUTH_HTTP_PASSWORD;
    }

    protected function addAuthListener($auth, $api)
    {
        if ( $auth == self::AUTH_HTTP_PASSWORD) {
            $listener = new BasicAuthListener(
                $this->credentials['username'],
                $this->credentials['password-or-token']
            );

        } else {
            $listener = new OAuthListener(
                array(
                    'oauth_consumer_key' => $this->credentials['password-or-token'],
                    'oauth_consumer_secret' => $this->credentials['secret']
                )
            );
        }

        $api->getClient()->addListener($listener);
    }

    protected function getCredentials()
    {
        if( isset($this->credentials['secret']) ) {
            return new OAuth(
                array(
                    'oauth_consumer_key' => $this->credentials['password-or-token'],
                    'oauth_consumer_secret' => $this->credentials['secret']
                ));
        }

        return new Basic($this->credentials['username'], $this->credentials['password-or-token']);
    }
} 