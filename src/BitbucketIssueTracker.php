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

use Gush\Adapter\Client\BitbucketIssueTrackerClient;
use Gush\Util\ArrayUtil;

/**
 * @author Raul Rodriguez <raulrodriguez782@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BitbucketIssueTracker extends BaseIssueTracker
{
    use BitbucketAdapter;

    /**
     * {@inheritdoc}
     */
    public function openIssue($subject, $body, array $options = [])
    {
        $response = $this->client->apiIssues()->create(
            $this->getUsername(),
            $this->getRepository(),
            array_merge(
                $options,
                [
                    'title' => $subject,
                    'content' => $body
                ]
            )
        );

        $resultArray = json_decode($response->getContent(), true);

        return $resultArray['local_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIssue($id)
    {
        $response = $this->client->apiIssues()->get(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );

        $resultArray = json_decode($response->getContent(), true);
        $issue = $this->adaptIssueStructure($resultArray);

        return $issue;
    }

    /**
     * {@inheritdoc}
     */
    public function getIssueUrl($id)
    {
        return sprintf('%s/%s/%s/issue/%d', $this->domain, $this->getUsername(), $this->getRepository(), $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getIssues(array $parameters = [], $page = 1, $perPage = 30)
    {
        // FIXME is not respecting the pagination

        $response = $this->client->apiIssues()->all(
            $this->getUsername(),
            $this->getRepository(),
            $this->prepareParameters($parameters)
        );

        $resultArray = json_decode($response->getContent(), true);

        $issues = [];

        foreach ($resultArray['issues'] as $issue) {
            $issues[] = $this->adaptIssueStructure($issue);
        }

        return $issues;
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

        //if (isset('labels'))



        $this->client->apiIssues()->update(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $this->prepareParameters($parameters)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function closeIssue($id)
    {
        $this->client->apiIssues()->update(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            ['status' => 'resolved']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createComment($id, $message)
    {
        $response = $this->client->apiIssues()->comments()->create(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $message
        );

        $resultArray = json_decode($response->getContent(), true);

        return ['number' => $resultArray['comment_id']];
    }

    /**
     * {@inheritdoc}
     */
    public function getComments($id)
    {
        $response = $this->client->apiIssues()->all(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );

        $resultArray = json_decode($response->getContent(), true);

        $comments = [];

        foreach ($resultArray as $comment) {
            $comments[] = [
                'id' => $comment['comment_id'],
                'url' => sprintf(
                    '%s/%s/%s/issue/%d/#comment-%d',
                    $this->domain,
                    $this->username,
                    $this->getRepository(),
                    $id,
                    $comment['comment_id']
                ),
                'user' => ['login' => $comment['author_info']['username']],
                'body' => $comment['content'],
                'created_at' => new \DateTime($comment['utc_created_on']),
                'updated_at' => !empty($comment['utc_updated_on']) ? new \DateTime($comment['utc_updated_on']) : null,
            ];
        }

        return $comments;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabels()
    {
        // use types and components for providing available labels
        throw new \Exception("Pending implementation");
    }

    /**
     * {@inheritdoc}
     */
    public function getMilestones(array $parameters = [])
    {
        $response = $this->client->apiIssues()->milestones()->all(
            $this->getUsername(),
            $this->getRepository()
        );

        $resultArray = json_decode($response->getContent(), true);

        return ArrayUtil::getValuesFromNestedArray(
            $resultArray,
            'name'
        );
    }

    protected function adaptIssueStructure(array $issue)
    {
        $labels = [
            $issue['metadata']['kind'],
            $issue['priority'],
        ];

        if (!empty($issue['metadata']['component'])) {
            $labels[] = $issue['metadata']['component'];
        }

        return [
            'url'          => $this->getIssueUrl($issue['local_id']),
            'number'       => $issue['local_id'],
            'state'        => $issue['status'],
            'title'        => $issue['title'],
            'body'         => $issue['content'],
            'user'         => $issue['reported_by']['username'],
            'labels'       => $labels,
            'assignee'     => (isset($issue['responsible']) ? $issue['responsible']['username'] : ''),
            'milestone'    => (isset($issue['metadata']['milestone']) && !is_null($issue['metadata']['milestone'])) ? $issue['metadata']['milestone'] : null,
            'created_at'   => new \DateTime($issue['utc_created_on']),
            'updated_at'   => !empty($issue['utc_last_updated']) ? new \DateTime($issue['utc_last_updated']) : null,
            'closed_by'    => null,
            'pull_request' => false,
        ];
    }

    protected function adaptPullRequestStructure(array $pr)
    {
        return [
            'url'          => $pr['html_url'],
            'number'       => $pr['number'],
            'state'        => $pr['state'],
            'title'        => $pr['title'],
            'body'         => $pr['body'],
            'labels'       => [],
            'milestone'    => null,
            'created_at'   => new \DateTime($pr['created_at']),
            'updated_at'   => !empty($pr['updated_at']) ? new \DateTime($pr['updated_at']) : null,
            'user'         => $pr['user']['login'],
            'assignee'     => null,
            'merge_commit' => null, // empty as GitHub doesn't provide this yet, merge_commit_sha is deprecated and not meant for this
            'merged'       => isset($pr['merged_by']) && isset($pr['merged_by']['login']),
            'merged_by'    => isset($pr['merged_by']) && isset($pr['merged_by']['login']) ? $pr['merged_by']['login'] : '',
            'head' => [
                'ref' =>  $pr['head']['ref'],
                'sha'  => $pr['head']['sha'],
                'user' => $pr['head']['user']['login'],
                'repo' => $pr['head']['repo']['name'],
            ],
            'base' => [
              'ref'   => $pr['base']['ref'],
              'label' => $pr['base']['label'],
              'sha'   => $pr['base']['sha'],
              'repo'  => $pr['base']['repo']['name'],
            ],
        ];
    }
}
