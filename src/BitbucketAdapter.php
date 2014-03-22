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

use Github\HttpClient\CachedHttpClient;
use Gush\Adapter\Decorator\BitbucketClientDecorator as Client;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Raul Rodriguez <raulrodriguez782@gmail.com>
 */
class BitbucketAdapter extends BaseAdapter
{
    const NAME = 'bitbucket';

    /**
     * @var string|null
     */
    protected $url;

    /**
     * @var string|null
     */
    protected $domain;

    /**
     * @var Client|null
     */
    protected $client;

    /**
     * @var string
     */
    protected $authenticationType = 'http_password';

    /**
     * Initializes the Adapter
     *
     * @return void
     */
    protected function initialize()
    {
        $this->client = $this->buildBitbucketClient();
    }

    /**
     * @return Client
     */
    protected function buildBitbucketClient()
    {
        $config = $this->configuration->get('bitbucket');
        $cachedClient = new CachedHttpClient([
            'cache_dir' => $this->configuration->get('cache-dir'),
            'base_url'  => $config['base_url']
        ]);

        $client = new Client($cachedClient);
        $this->url = rtrim($config['base_url'], '/');
        $this->domain = rtrim($config['repo_domain_url'], '/');

        return $client;
    }

    public static function doConfiguration(OutputInterface $output, DialogHelper $dialog)
    {
        $config = [];

        $validator = function ($field) {
            if (empty($field)) {
                throw new \InvalidArgumentException('The field cannot be empty.');
            }

            return $field;
        };

        $output->writeln('<comment>Enter your Bitbucket Secret: </comment>');
        $secretText = 'secret: ';
        $config['secret'] = $dialog->askHiddenResponseAndValidate(
            $output,
            $secretText,
            $validator
        );

        $output->writeln('<comment>Enter your Bitbucket URL: </comment>');
        $config['base_url'] = $dialog->askAndValidate(
            $output,
            'Api url: ',
            function ($url) {
                return filter_var($url, FILTER_VALIDATE_URL);
            },
            false,
            'https://bitbucket.org/api/2.0/'
        );

        $config['repo_domain_url'] = $dialog->askAndValidate(
            $output,
            'Repo domain url: ',
            function ($field) {
                return $field;
            },
            false,
            'bitbucket.org'
        );

        return $config;
    }

    /**
     * @return Boolean
     */
    public function authenticate()
    {
        $credentials = $this->configuration->get('authentication');

        if ( 'http_password' === $credentials['http-auth-type'] ) {
            $this->client->addListener('basic_auth_listener', array(
                'user' => $credentials['username'],
                'pass' => $credentials['password-or-token']
            ));
        }

        if( '' === $credentials['http-auth-type'] ) {
            $this->client->addListener('oauth_listenr', array(
                'user' => $credentials['username'],
                'pass' => $credentials['password-or-token'],
                'secret' => $credentials['secret'],
            ));
        }

        $this->client->authenticate();

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        // TODO: Implement isAuthenticated() method.
    }

    /**
     * Returns the URL for generating a token.
     * If the adapter does not support tokens, returns null
     *
     * @return null|string
     */
    public function getTokenGenerationUrl()
    {
        // TODO: Implement getTokenGenerationUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function createFork($org)
    {
        // TODO: Implement createFork() method.
    }

    /**
     * {@inheritdoc}
     */
    public function openIssue($subject, $body, array $options = [])
    {
        // TODO: Implement openIssue() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getIssue($id)
    {
        // TODO: Implement getIssue() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getIssueUrl($id)
    {
        // TODO: Implement getIssueUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getIssues(array $parameters = [])
    {

    }

    /**
     * {@inheritdoc}
     */
    public function updateIssue($id, array $parameters)
    {
        // TODO: Implement updateIssue() method.
    }

    /**
     * {@inheritdoc}
     */
    public function closeIssue($id)
    {
        // TODO: Implement closeIssue() method.
    }

    /**
     * {@inheritdoc}
     */
    public function createComment($id, $message)
    {
        // TODO: Implement createComment() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getComments($id)
    {
        // TODO: Implement getComments() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getLabels()
    {
        // TODO: Implement getLabels() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getMilestones(array $parameters = [])
    {
        // TODO: Implement getMilestones() method.
    }

    /**
     * {@inheritdoc}
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = [])
    {
        // TODO: Implement openPullRequest() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequest($id)
    {
        // TODO: Implement getPullRequest() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestUrl($id)
    {
        // TODO: Implement getPullRequestUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestCommits($id)
    {
        // TODO: Implement getPullRequestCommits() method.
    }

    /**
     * {@inheritdoc}
     */
    public function mergePullRequest($id, $message)
    {
        // TODO: Implement mergePullRequest() method.
    }

    /**
     * {@inheritdoc}
     */
    public function createRelease($name, array $parameters = [])
    {
        // TODO: Implement createRelease() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getReleases()
    {
        // TODO: Implement getReleases() method.
    }

    /**
     * {@inheritdoc}
     */
    public function removeRelease($id)
    {
        // TODO: Implement removeRelease() method.
    }

    /**
     * {@inheritdoc}
     */
    public function createReleaseAssets($id, $name, $contentType, $content)
    {
        // TODO: Implement createReleaseAssets() method.
    }
}