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

use Bitbucket\API\User;

/**
 * @author Raul Rodriguez <raulrodriguez782@gmail.com>
 */
class BitbucketRepositoryClient extends BitbucketClient
{
    public function fork($username, $repository, $org)
    {
        $api = $this->api('Bitbucket\API\Repositories\Repository');

        return $api->fork($username, $repository, $org, []);
    }

    public function getPullRequests($username, $repository, array $params = [])
    {
        $api = $this->api('Bitbucket\API\Repositories\PullRequests');

        return $api->all($username, $repository, $params);
    }

    public function getPullRequest($username, $repository, $id)
    {
        $api = $this->api('Bitbucket\API\Repositories\PullRequests');

        return $api->get($username, $repository, $id);
    }

    public function getPullRequestCommits($username, $repository, $id)
    {
        $api = $this->api('Bitbucket\API\Repositories\PullRequests');

        return $api->commits($username, $repository, $id);
    }

    public function createPullRequest($username, $repository, $params = [])
    {
        $api = $this->api('Bitbucket\API\Repositories\PullRequests');

        return $api->create($username, $repository, $params);
    }

    public function mergePullRequests($username, $repository, $id, array $params = [])
    {
        $api = $this->api('Bitbucket\API\Repositories\PullRequests');

        return $api->accept($username, $repository, $id, $params);
    }

    public function createComment($username, $repository, $id, $message)
    {
        $api = $this->api('Bitbucket\API\Repositories\PullRequests\Comments');

        return $api->create($username, $repository, $id, $message);
    }

    public function getComments($username, $repository, $id)
    {
        $api = $this->api('Bitbucket\API\Repositories\PullRequests\Comments');

        return $api->all($username, $repository, $id);
    }

    public function closePullRequest($username, $repository, $id)
    {
        $api = $this->api('Bitbucket\API\Repositories\PullRequests');

        return $api->decline($username, $repository, $id);
    }

    public function getReleases($username, $repository)
    {
        $api = $this->api('Bitbucket\API\Repositories\Repository');

        return $api->tags($username, $repository);
    }

    public function getMilestones()
    {
        // BitBucket has no support for repository milestones, only issues
        return [];
    }
}
