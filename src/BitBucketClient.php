<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

use Bitbucket\API;
use Bitbucket\API\Http\Client;
use Bitbucket\API\Http\ClientInterface;
use Bitbucket\API\Http\Listener\BasicAuthListener;
use Bitbucket\API\Http\Listener\OAuthListener;
use Bitbucket\API\User;
use Gush\Adapter\Listener\ErrorListener;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BitBucketClient
{
    /**
     * Constant for authentication method.
     */
    const AUTH_HTTP_PASSWORD = 'http_password';

    /**
     * Constant for authentication method.
     */
    const AUTH_HTTP_TOKEN = 'http_token';

    /**
     * The Buzz instance used to communicate with Gitlab
     *
     * @var Client
     */
    protected $httpClient;

    /**
     * @var ErrorListener
     */
    protected $errorListener;

    /**
     * @var array
     */
    protected $options;

    /**
     * Constructor.
     *
     * @param array           $options
     * @param ClientInterface $httpClient
     */
    public function __construct(array $options = [], ClientInterface $httpClient = null)
    {
        $this->errorListener = new ErrorListener();

        $this->httpClient = $httpClient ?: new Client($options);
        $this->httpClient->addListener($this->errorListener);
        $this->options = $options;
    }

    public function apiRepository()
    {
        $api = new API\Repositories\Repository();
        $this->httpClient->setApiVersion('1.0');

        $api->setClient($this->httpClient);

        return $api;
    }

    public function apiRepositories()
    {
        $api = new API\Repositories();
        $api->setClient($this->httpClient);

        return $api;
    }

    public function apiUser()
    {
        $api = new API\User();
        $api->setClient($this->httpClient);

        return $api;
    }

    public function apiCommits()
    {
        $api = new API\Repositories\Commits();
        $api->setClient($this->httpClient);

        return $api;
    }

    public function apiPullRequests()
    {
        $api = new API\Repositories\PullRequests();
        $api->setClient($this->httpClient);

        return $api;
    }

    public function apiIssues()
    {
        $api = new API\Repositories\Issues();
        $this->httpClient->setApiVersion('1.0');
        $api->setClient($this->httpClient);

        return $api;
    }

    /**
     * Authenticates a user.
     *
     * @param array       $credentials BitBucket authentication credentials
     * @param null|string $authMethod  One of the AUTH_* class constants
     */
    public function authenticate($credentials, $authMethod = null)
    {
        if ($authMethod === self::AUTH_HTTP_PASSWORD) {
            $listener = new BasicAuthListener(
                $credentials['username'],
                $credentials['password']
            );
        } else {
            $listener = new OAuthListener(
                [
                    'oauth_consumer_key' => $credentials['key'],
                    'oauth_consumer_secret' => $credentials['secret']
                ]
            );
        }

        $this->httpClient->addListener($listener);
    }

    /**
     * @return ClientInterface
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param ClientInterface $httpClient
     */
    public function setHttpClient(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function disableErrorListener($permanent = false)
    {
        $this->errorListener->disableListener($permanent);
    }

    public function enableListener()
    {
        $this->errorListener->enableListener();
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
