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

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BitBucketConfigurator is the Configurator class for BitBucket configuring.
 *
 * Overwriting because OAuth requires a secret.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BitBucketConfigurator extends DefaultConfigurator
{
    /**
     * Constructor.
     *
     * @param DialogHelper $dialog  DialogHelper instance
     * @param string       $label   Label of the Configurator (eg. BitBucket or BitBucket IssueTracker)
     * @param string       $apiUrl  Default URL to API service (eg. 'https://api.bitbucket.org/')
     * @param string       $repoUrl Default URL to repository (eg. 'https://bitbucket.org')
     */
    public function __construct(DialogHelper $dialog, $label, $apiUrl, $repoUrl)
    {
        $this->dialog = $dialog;
        $this->label = $label;
        $this->apiUrl = $apiUrl;
        $this->repoUrl = $repoUrl;

        $authenticationOptions = [
            0 => ['Password', 'Password', self::AUTH_HTTP_PASSWORD],
            1 => ['OAuth', 'Key', self::AUTH_HTTP_TOKEN]
        ];

        $this->authenticationOptions = $authenticationOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function interact(InputInterface $input, OutputInterface $output)
    {
        $config = [];

        $authenticationLabels = array_map(
            function ($value) {
                return $value[0];
            },
            $this->authenticationOptions
        );

        $authenticationType = $this->dialog->select(
            $output,
            'Choose '.$this->label.' authentication type:',
            $authenticationLabels,
            0
        );

        $config['authentication'] = [];
        $config['authentication']['http-auth-type'] = $this->authenticationOptions[$authenticationType][2];

        $config['authentication']['username'] = $this->dialog->askAndValidate(
            $output,
            'Username: ',
            [$this, 'validateNoneEmpty']
        );

        $config['authentication']['password-or-token'] = $this->dialog->askHiddenResponseAndValidate(
            $output,
            $this->authenticationOptions[$authenticationType][1].': ',
            [$this, 'validateNoneEmpty']
        );

        if (static::AUTH_HTTP_TOKEN === $config['authentication']['http-auth-type']) {
            $config['authentication']['secret'] = $this->dialog->askHiddenResponseAndValidate(
                $output,
                'Secret: ',
                [$this, 'validateNoneEmpty']
            );
        }

        // Not really configurable at the moment, so hard-configured
        $config['base_url'] = $this->apiUrl;
        $config['repo_domain_url'] = $this->repoUrl;

        return $config;
    }
}
