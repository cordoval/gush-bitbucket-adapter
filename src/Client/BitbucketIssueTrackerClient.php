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

/**
 * @author Raul Rodriguez <raulrodriguez782@gmail.com>
 */
class BitbucketIssueTrackerClient extends BitbucketClient
{
    public function getIssues($username, $repository, array $parameters)
    {
        $api = $this->api('Bitbucket\API\Repositories\Issues');

        return $api->all(
            $username,
            $repository,
            $this->prepareParameters($parameters)
        );
    }

    public function openIssue($username, $repository, array $parameters)
    {
        $api = $this->api('Bitbucket\API\Repositories\Issues');

        return $api->create(
            $username,
            $repository,
            $this->prepareParameters($parameters)
        );
    }

    public function getIssue($username, $repository, $id)
    {
        $api = $this->api('Bitbucket\API\Repositories\Issues');

        return $api->get(
            $username,
            $repository,
            $id
        );
    }

    public function updateIssue($username, $repository, $id, array $parameters)
    {
        $api = $this->api('Bitbucket\API\Repositories\Issues');

        return $api->update(
            $username,
            $repository,
            $id,
            $this->prepareParameters($parameters)
        );
    }

    public function createComment($username, $repository, $id, $message)
    {
        $api = $this->api('Bitbucket\API\Repositories\Issues\Comments');

        return $api->create(
            $username,
            $repository,
            $id,
            $message
        );
    }

    public function getComments($username, $repository, $id)
    {
        $api = $this->api('Bitbucket\API\Repositories\Issues\Comments');

        return $api->all(
            $username,
            $repository,
            $id
        );
    }

    public function getMilestones($username, $repository)
    {
        $api = $this->api('Bitbucket\API\Repositories\Issues\Milestones');

        return $api->all(
            $username,
            $repository
        );
    }
}
