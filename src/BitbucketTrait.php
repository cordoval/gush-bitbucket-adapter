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

use Gush\Adapter\Client\BitbucketClient as Client;
use Gush\Config;

/**
 * @author Raul Rodriguez <raulrodriguez782@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
trait BitbucketTrait
{
    /**
     * @var string|null
     */
    protected $url;

    /**
     * @var string|null
     */
    protected $domain;

    /**
     * @var string
     */
    protected $authenticationType = 'http_password';

    /**
     * @var bool
     */
    protected $isAuthenticated;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Gush\Config
     */
    protected $globalConfig;

    /**
     * @param array  $config
     * @param Config $globalConfig
     */
    public function __construct(array $config, Config $globalConfig)
    {
        $this->config = $config;
        $this->globalConfig = $globalConfig;
        $this->client = $this->buildBitbucketClient();
    }

    /**
     * @return Client
     */
    abstract protected function buildBitbucketClient();

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $credentials = $this->config['authentication'];

        if (Client::AUTH_HTTP_PASSWORD === $credentials['http-auth-type']) {
            $bitbucketCredentials = array(
                'username' => $credentials['username'],
                'password-or-token' => $credentials['password-or-token']
            );
        } else {
            $bitbucketCredentials = array(
                'username' => $credentials['username'],
                'password-or-token' => $credentials['password-or-token'],
                'secret' => $credentials['secret'],
            );
        }

        $this->client->setCredentials($bitbucketCredentials);
        $this->isAuthenticated = $this->client->authenticate();

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenGenerationUrl()
    {
        if (isset($this->config['authentication'])) {
            return sprintf(
                'https://bitbucket.org/account/user/%s/api',
                $this->config['authentication']['username']
            );
        }

        return null;
    }
}
