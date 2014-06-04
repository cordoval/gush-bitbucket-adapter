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

use Gush\Exception\AdapterException;
use Herrera\Version\Parser;
use Herrera\Version\Validator as VersionValidator;

/**
 * @author Raul Rodriguez <raulrodriguez782@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BitbucketRepoAdapter extends BaseAdapter
{
    use BitbucketAdapter;

    /**
     * {@inheritdoc}
     */
    public function supportsRepository($remoteUrl)
    {
        return false !== stripos($remoteUrl, 'bitbucket.org');
    }

    /**
     * {@inheritdoc}
     */
    public function createFork($org)
    {
        $response = $this->client->apiRepository()->get(
            $this->getUsername(),
            $this->getRepository()
        )->getContent();

        $resultArray = json_decode($response, true);

        if ('no_forks' === $resultArray['fork_policy']) {
            throw new AdapterException('Forking is not allowed for this repository.');
        }

        if ('git' !== $resultArray['scm']) {
            throw new AdapterException('Repository type is not git, only git supported.');
        }

        if ($this->getUsername() === $resultArray['owner']['username']) {
            // BitBucket always forks to the current user account, so prefix it
            // with the org to make it unique
            $name = $org.'-'.$resultArray['name'];
        } else {
            $name = $resultArray['name'];
        }

        $response = $this->client->apiRepository()->fork(
            $this->getUsername(),
            $this->getRepository(),
            $name,
            [
                'is_private' => $response
            ]
        );

        $resultArray = json_decode($response->getContent(), true);

        return [
            'git_url' => 'git@bitbucket.org:'.$resultArray['owner'].'/'.$resultArray['slug'].'.git',
            'html_url' => $this->domain.'/'.$resultArray['owner'].'/'.$resultArray['slug'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createComment($id, $message)
    {
        $response = $this->client->apiPullRequests()->comments()->create(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $message
        );

        $resultArray = json_decode($response->getContent(), true);

        return sprintf(
            '%s/%s/%s/pull-request/%d/_/diff#comment-%d',
            $this->domain,
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $resultArray['comment_id']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getComments($id)
    {
        $response = $this->client->apiPullRequests()->comments()->all(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );

        $fetchedComments = json_decode($response->getContent(), true);

        $comments = [];

        foreach ($fetchedComments['values'] as $comment) {
            $comments[] = [
                'id' => $comment['id'],
                'url' => $comment['links']['html'],
                'body' => $comment['content']['raw'],
                'user' => $comment['user']['username'],
                'created_at' => !empty($comment['created_on']) ? new \DateTime($comment['created_on']) : null,
                'updated_at' => !empty($comment['updated_on']) ? new \DateTime($comment['updated_on']) : null,
            ];
        }

        return $comments;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabels()
    {
        throw new \Exception('BitBucket doesn\'t support components (labels) for pull request.');
    }

    /**
     * {@inheritdoc}
     */
    public function getMilestones(array $parameters = [])
    {
        throw new \Exception('BitBucket doesn\'t support Milestones for pull request.');
    }

    /**
     * {@inheritdoc}
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = [])
    {
        list($sourceOrg, $sourceBranch) = explode(':', $head, 2);

        $params = [
            'title' => $subject,
            'description' => $body,
            'source' => [
                'branch' => [
                    'name' => $sourceBranch,
                ],
            ],
            'destination' => [
                'branch' => [
                    'name' => $base,
                ],
            ],
        ];

        if (null !== $this->getUsername() && $sourceOrg !== $this->getUsername()) {
            $params['source']['repository'] = [
                'full_name' => $sourceOrg.'/'.$this->getRepository(),
            ];
        }

        $response = $this->client->apiPullRequests()->create(
            $this->getUsername(),
            $this->getRepository(),
            $params
        );

        $resultArray = json_decode($response->getContent(), true);

        return [
            'html_url' => $resultArray['links']['html']['href'],
            'number' => $resultArray['id']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequest($id)
    {
        $response = $this->client->apiPullRequests()->get(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );

        $resultArray = json_decode($response->getContent(), true);

        return $this->adaptPullRequestStructure($resultArray);
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestUrl($id)
    {
        return sprintf(
            '%s/%s/%s/pull-requests/%d',
            $this->domain,
            $this->getUsername(),
            $this->getRepository(),
            $id
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestCommits($id)
    {
        $response = $this->client->apiPullRequests()->commits(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );

        // FIXME this will not get all commits due to page-limiting

        $resultArray = json_decode($response->getContent(), true);

        $commits = [];

        foreach ($resultArray['values'] as $commit) {
            $commits[] = [
                'sha' => $commit['hash'],
                'user' => $commit['author']['user']['username'],
                'message' => trim($commit['message']),
            ];
        }

        return $commits;
    }

    /**
     * {@inheritdoc}
     */
    public function mergePullRequest($id, $message)
    {
        $response = $this->client->apiPullRequests()->accept(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            ['message' => $message]
        );

        $resultArray = json_decode($response->getContent(), true);
        if ('MERGED' !== $resultArray['state']) {
            throw new AdapterException($response->getContent());
        }

        return $resultArray['merge_commit']['hash'];
    }

    /**
     * {@inheritdoc}
     */
    public function updatePullRequest($id, array $parameters)
    {
        // BitBucket requires the existing values to be passed with it
        // At the moment only PullRequestAssignCommand uses this method directly
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function closePullRequest($id)
    {
        $this->client->apiPullRequests()->decline(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequests($state = null, $page = 1, $perPage = 30)
    {
        $response = $this->client->apiPullRequests()->all(
            $this->getUsername(),
            $this->getRepository(),
            [
                'state' => $state
            ]
        );

        $resultArray = json_decode($response->getContent(), true);
        $prs = [];

        foreach ($resultArray['values'] as $pr) {
            $prs[] = $this->adaptPullRequestStructure($pr);
        }

        return $prs;
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
        // BitBucket doesn't support this yet, use the CommandHelper for executing a `git tag` operation
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function getReleases()
    {
        $response = $this->client->apiRepository()->tags(
            $this->getUsername(),
            $this->getRepository()
        );

        $resultArray = json_decode($response->getContent(), true);
        $releases = [];

        foreach ($resultArray as $name => $release) {
            $version = ltrim($name, 'v');

            $releases[] = [
                'url' => sprintf('%s/%s/%s/commits/tag/%s', $this->domain, $this->getUsername(), $this->getRepository(), $name),
                'id' => null,
                'name' => $name,
                'tag_name' => $name,
                'body' => $release['message'],
                'draft' => false,
                'prerelease' => VersionValidator::isVersion($version) && !Parser::toVersion($version)->isStable(),
                'created_at' => new \DateTime($release['utctimestamp']),
                'updated_at' => null,
                'published_at' => new \DateTime($release['utctimestamp']),
                'user' => $release['author'],
            ];
        }

        return $releases;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRelease($id)
    {
        // BitBucket doesn't support this yet
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function createReleaseAssets($id, $name, $contentType, $content)
    {
        throw new \Exception('BitBucket doesn\'t support release assets.');
    }

    protected function adaptPullRequestStructure(array $pr)
    {
        list($sourceOrg,) = explode('/', $pr['source']['repository']['full_name'], 2);

        return [
            'url' => $pr['links']['html']['href'],
            'number' => $pr['id'],
            'state' => $pr['state'],
            'title' => $pr['title'],
            'body' => $pr['description'],
            'labels' => [], // unsupported
            'milestone' => null, // unsupported
            'created_at' => new \DateTime($pr['created_on']),
            'updated_at' => !empty($pr['updated_on']) ? new \DateTime($pr['updated_on']) : null,
            'user' => $pr['author']['username'],
            'assignee' => null, // unsupported, only multiple
            'merge_commit' => $pr['merge_commit'],
            'merged' => 'merged' === strtolower($pr['state']) && isset($pr['closed_by']),
            'merged_by' => isset($pr['closed_by']) ? $pr['closed_by'] : '',
            'head' => [
                'ref' => $pr['source']['branch']['name'],
                'sha' => $pr['source']['commit']['hash'],
                'user' => $sourceOrg,
                'repo' => $pr['source']['branch']['name'],
            ],
            'base' => [
              'ref' => $pr['destination']['branch']['name'],
              'label' => $pr['destination']['branch']['name'],
              'sha' => $pr['destination']['commit']['hash'],
              'repo' => $pr['destination']['repository']['name'],
            ],
        ];
    }
}
