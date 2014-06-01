<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter\Listener;

use Bitbucket\API\Http\Listener\ListenerInterface;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;
use Gush\Exception\AdapterException;

class ErrorListener implements ListenerInterface
{
    public function preSend(RequestInterface $request)
    {
        // noop
    }

    public function postSend(RequestInterface $request, MessageInterface $response)
    {
        if (!$response->isSuccessful()) {
            $resultArray = json_decode($response->getContent(), true);

            if (isset($resultArray['error'])) {
                $errorMessage = $resultArray['error']['message'];
            } else {
                $errorMessage = 'No message found, raw content:'. $response->getContent();
            }

            throw new AdapterException($errorMessage);
        }
    }

    public function getName()
    {
        return 'error';
    }
}
