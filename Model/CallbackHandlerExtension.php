<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Owebia\SharedPhpConfig\Model;

class CallbackHandlerExtension implements CallbackHandlerExtensionInterface
{
    /**
     * @var \Owebia\SharedPhpConfig\Model\CallbackHandler
     */
    protected $callbackHandler;

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this, $method)) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            return call_user_func_array([ $this, $method ], $arguments);
        } else {
            throw new \BadMethodCallException("Method $method not found");
        }
    }

    /**
     * @param \Owebia\SharedPhpConfig\Model\CallbackHandler $callbackHandler
     * @return $this
     */
    public function setCallbackHandler($callbackHandler)
    {
        $this->callbackHandler = $callbackHandler;
        return $this;
    }

    /**
     * @return \Owebia\SharedPhpConfig\Helper\Registry
     */
    public function getRegistry()
    {
        return $this->callbackHandler->getRegistry();
    }
}
