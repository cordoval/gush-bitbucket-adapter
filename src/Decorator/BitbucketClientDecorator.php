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
use Bitbucket\API\User;

/**
 * @author Raul Rodriguez <raulrodriguez782@gmail.com>
 */
class BitbucketClientDecorator
{
    protected $user = null;

    protected $credentials = array();

    const AUTH_HTTP_PASSWORD = 'http_password';

    public function __construct()
    {
        $this->user = new User();
    }

    /**
     * Add Listener
     *
     * @param string $auth
     * @param array $credentials
     *
     * @return void
     */
    public function addListener($auth, $credentials)
    {
        if( $auth == 'http_password' ) {

            $this->user->getClient()
                ->addListener(
                new BasicAuthListener($credentials['username'], $credentials['password-or-token'])
            );
        } else {

            $oauthParams = array(
                'oauth_consumer_key' => $credentials['password-or-token'],
                'oauth_consumer_secret' => $credentials['secret']
            );

            $this->user->getClient()
                ->addListener(
                    new OAuthListener($oauthParams)
                );
        }
        $this->credentials = $credentials;
    }

    /**
     * Authenticate Bitbucket
     *
     * @return void
     */
    public function authenticate()
    {
        $this->user->get();
    }

    protected function getCredentials()
    {
        if( isset($credentials['secret']) ) {
            return new OAuth(
                array(
                    'oauth_consumer_key' => $this->credentials['password-or-token'],
                    'oauth_consumer_secret' => $this->credentials['secret']
                ));
        }

        return new Basic($this->credentials['username'], $this->credentials['password-or-token']);

    }
} 