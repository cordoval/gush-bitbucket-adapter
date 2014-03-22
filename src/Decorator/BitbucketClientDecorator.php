<?php
/**
 * @author: Raul Rodriguez - raulrodriguez782@gmail.com
 * @created: 3/21/14 - 11:47 PM
 */

namespace Gush\Adapter\Decorator;

use Bitbucket\API\Http\Listener\OAuthListener;
use Bitbucket\API\User;
use Bitbucket\API\Http\Listener\BasicAuthListener;
use Bitbucket\API;

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
        if($auth == 'http_password') {

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
        if(isset($credentials['secret'])) {
            return new API\Authentication\OAuth(
                array(
                    'oauth_consumer_key' => $this->credentials['password-or-token'],
                    'oauth_consumer_secret' => $this->credentials['secret']
                ));
        } else {
            return new API\Authentication\Basic($this->credentials['username'], $this->credentials['password-or-token']);
        }
    }

} 