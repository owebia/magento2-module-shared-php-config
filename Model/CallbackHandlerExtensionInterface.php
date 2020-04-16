<?php
/**
 * Copyright © 2019-2020 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Owebia\SharedPhpConfig\Model;

interface CallbackHandlerExtensionInterface
{
    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments);

    /**
     * @param \Owebia\SharedPhpConfig\Model\CallbackHandler $callbackHandler
     * @return $this
     */
    public function setCallbackHandler($callbackHandler);
}
