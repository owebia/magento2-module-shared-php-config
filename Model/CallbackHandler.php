<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Owebia\SharedPhpConfig\Model;

class CallbackHandler
{
    /**
     * @var \Owebia\SharedPhpConfig\Helper\Registry
     */
    protected $registry;

    /**
     * @var \Owebia\SharedPhpConfig\Model\CallbackHandlerExtensionInterface
     */
    protected $callbackHandlerExtension;

    /**
     * @param \Owebia\SharedPhpConfig\Model\CallbackHandlerExtensionInterface $callbackHandlerExtension
     */
    public function __construct(
        \Owebia\SharedPhpConfig\Model\CallbackHandlerExtensionInterface $callbackHandlerExtension
    ) {
        $this->callbackHandlerExtension = $callbackHandlerExtension;
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->callbackHandlerExtension
            ->setCallbackHandler($this)
            ->__call($method, $arguments);
    }

    /**
     * @param string $callback
     * @return bool
     */
    public function hasCallback($callback)
    {
        return method_exists($this, $callback) || method_exists($this->callbackHandlerExtension, $callback);
    }

    /**
     * @return string
     */
    public function helpCallback()
    {
        return "The result of the help call is visible in the backoffice";
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function errorCallback($msg)
    {
        throw new \Magento\Framework\Exception\LocalizedException(__($msg));
    }

    /**
     * @return string
     */
    public function appendParsingError($msg)
    {
        return $msg;
    }

    /**
     * @param \Owebia\SharedPhpConfig\Helper\Registry $registry
     */
    public function setRegistry($registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return \Owebia\SharedPhpConfig\Helper\Registry $registry
     */
    public function getRegistry()
    {
        return $this->registry;
    }
}
