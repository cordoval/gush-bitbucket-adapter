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

use Gush\Config;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BitbucketFactory
{
    public static function createAdapter($adapterConfig, Config $config)
    {
        return new BitbucketAdapter($adapterConfig, $config);
    }

    public static function createAdapterConfigurator(HelperSet $helperSet)
    {
        $configurator = new BitBucketConfigurator(
            $helperSet->get('dialog'),
            'Bitbucket',
            'https://api.bitbucket.org',
            'https://bitbucket.org'
        );

        return $configurator;
    }

    public static function createIssueTracker($adapterConfig, Config $config)
    {
        return new BitbucketIssueTracker($adapterConfig, $config);
    }

    public static function createIssueTrackerConfigurator(HelperSet $helperSet)
    {
        $configurator = new BitBucketConfigurator(
            $helperSet->get('dialog'),
            'Bitbucket issue tracker',
            'https://api.bitbucket.org',
            'https://bitbucket.org'
        );

        return $configurator;
    }
}
