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
     * @var boolean
     */
    protected $isAuthenticated;

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
        $config['configuration']['secret'] = $dialog->askHiddenResponseAndValidate(
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
            'https://api.bitbucket.org/'
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

        if (Client::AUTH_HTTP_PASSWORD === $credentials['http-auth-type']) {
            $bitbucketCredentials = array(
                'username' => $credentials['username'],
                'password-or-token' => $credentials['password-or-token']
            );
        }

        if (Client::AUTH_HTTP_TOKEN === $credentials['http-auth-type']) {
            $bitbucket = $this->configuration->get('bitbucket');
            $bitbucketCredentials = array(
                'username' => $credentials['username'],
                'password-or-token' => $credentials['password-or-token'],
                'secret' => $bitbucket['configuration']['secret'],
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
     * Returns the URL for generating a token.
     * If the adapter does not support tokens, returns null
     *
     * @return null|string
     */
    public function getTokenGenerationUrl()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function createFork($org)
    {
        $response = $this->client->fork(
            $this->getUsername(),
            $this->getRepository(),
            $org);

        $domain = "https://bitbucket.org";
        $resultArray = json_decode($response->getContent(), true);

        return [
            'remote_url' => $domain . '/' . $resultArray['owner'] . '/' . $resultArray['slug']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function openIssue($subject, $body, array $options = [])
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function getIssue($id)
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function getIssueUrl($id)
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function getIssues(array $parameters = [])
    {
        $response = $this->client->issues(
            $this->getUsername(),
            $this->getRepository(),
            $parameters
        );

        $resultArray = json_decode($response->getContent(), true);

        $issuesArray = $this->adapt('issues', $resultArray['issues']);

        return $issuesArray;
    }

    private function adapt($api, $array)
    {
        $items = [];

        if ( $api == 'issues' ) {

            foreach ($array as $item ) {
                $resourceParts = explode('/', strrev($item['resource_uri']), 2);
                $adaptedArray['number'] = $resourceParts[0];
                $adaptedArray['state'] = $item['status'];
                $adaptedArray['title'] = $item['title'];
                $adaptedArray['user'] = [];
                $adaptedArray['user']['login'] = $item['reported_by']['username'];
                $adaptedArray['assignee'] = [];
                $adaptedArray['assignee']['login'] = (isset($item['responsible']))
                    ? $item['responsible']['username']
                    : '';
                $adaptedArray['milestone'] = [];
                $adaptedArray['milestone']['title'] =
                    (isset($item['metadata']['milestone']) && !is_null($item['metadata']['milestone']))
                        ? $item['metadata']['milestone']
                        : ''
                ;
                $adaptedArray['labels'] = [];
                $adaptedArray['labels'][]= ['name' => $item['metadata']['kind']];
                $adaptedArray['labels'][] =['name' => $item['priority']];
                $adaptedArray['created_at'] = $item['utc_created_on'];

                $items[] = $adaptedArray;
            }
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function updateIssue($id, array $parameters)
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function closeIssue($id)
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function createComment($id, $message)
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function getComments($id)
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function getLabels()
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function getMilestones(array $parameters = [])
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = [])
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequest($id)
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestUrl($id)
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestCommits($id)
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function mergePullRequest($id, $message)
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function createRelease($name, array $parameters = [])
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function getReleases()
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function removeRelease($id)
    {
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function createReleaseAssets($id, $name, $contentType, $content)
    {
        throw new \Exception("Pending implementation");
    }
}
