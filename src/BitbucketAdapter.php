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
        $client = new Client();
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
            'Api url [https://api.bitbucket.org/]:',
            function ($url) {
                return filter_var($url, FILTER_VALIDATE_URL);
            },
            false,
            'https://api.bitbucket.org/'
        );

        $config['repo_domain_url'] = $dialog->askAndValidate(
            $output,
            'Repo domain url [bitbucket.org]: ',
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
        $response = $this->client->openIssue(
            $this->getUsername(),
            $this->getRepository(),
            array_merge($options, ['title' => $subject, 'content' => $body])
        );

        $resultArray = json_decode($response->getContent(), true);

        return ['number' => $resultArray['local_id'] ];
    }

    /**
     * {@inheritdoc}
     */
    public function getIssue($id)
    {
        $response = $this->client->getIssue(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );

        $resultArray = json_decode($response->getContent(), true);
        $issue = $this->adapt('getIssue', $resultArray);

        return $issue;
    }

    /**
     * {@inheritdoc}
     */
    public function getIssueUrl($id)
    {
        return sprintf('https://%s/%s/%s/issue/%d', $this->domain, $this->getUsername(), $this->getRepository(), $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getIssues(array $parameters = [])
    {
        $response = $this->client->getIssues(
            $this->getUsername(),
            $this->getRepository(),
            $parameters
        );

        $resultArray = json_decode($response->getContent(), true);

        $issuesArray = $this->adapt('getIssues', $resultArray['issues']);

        return $issuesArray;
    }

    /**
     * {@inheritdoc}
     */
    public function updateIssue($id, array $parameters)
    {
        if(isset($parameters['assignee'])) {
            $username = $parameters['assignee'];
            $parameters['responsible'] = $username;

            unset($parameters['assignee']);
        }

        $response = $this->client->updateIssue(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $parameters
        );

        $resultArray = json_decode($response->getContent(), true);

        return ['number' => $resultArray['local_id'] ];
    }

    /**
     * {@inheritdoc}
     */
    public function closeIssue($id)
    {
        $response = $this->client->updateIssue(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            ['status' => 'resolved']
        );

        $resultArray = json_decode($response->getContent(), true);

        return ['number' => $resultArray['local_id'] ];
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
    public function getPullRequests($state = null)
    {
        $response = $this->client->getPullRequests(
            $this->getUsername(),
            $this->getRepository(),
            array(
                'state' => $state
            )
        );

        $resultArray = json_decode($response->getContent(), true);
        $returnArray = [];

        foreach ($resultArray['values'] as $result) {
            $returnArray[] = [
                'number'      => $result['id'],
                'title'       => $result['title'],
                'state'       => $result['state'],
                'created_at'  => $result['created_on'],
                'head'        => [
                    'user' => [
                        'login' => $result['author']['username'],
                    ],
                ],
            ];
        }

        return $returnArray;
    }

    /**
     * {@inheritDoc}
     */
    public function getPullRequestStates()
    {
        return [
            'OPEN',
            'MERGED',
            'DECLINED',
        ];
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

    private function adapt($api, $array)
    {
        $result = [];

        if ( $api == 'getIssues' ) {

            foreach ($array as $item ) {
                $adaptedArray = [];
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

                $result[] = $adaptedArray;
            }
        } else if ($api == 'getIssue') {

            $result['number'] = $array['local_id'];
            $result['state'] = $array['status'];
            $result['user'] = [];
            $result['user']['login'] = $array['reported_by']['username'];
            $result['assignee'] = [];
            $result['assignee']['login'] = (isset($array['responsible']))
                ? $array['responsible']['username']
                : '';
            $result['title'] = $array['title'];
            $result['body'] = $array['content'];
            $result['milestone'] = [];
            $result['milestone']['title'] =
                (isset($array['metadata']['milestone']) && !is_null($array['metadata']['milestone']))
                    ? $array['metadata']['milestone']
                    : ''
            ;
            $result['labels'] = [];
            $result['labels'][]= ['name' => $array['metadata']['kind']];
            $result['labels'][] =['name' => $array['priority']];
        }

        return $result;
    }
}
