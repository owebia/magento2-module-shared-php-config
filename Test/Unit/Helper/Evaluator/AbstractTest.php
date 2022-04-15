<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Owebia\SharedPhpConfig\Test\Unit\Helper\Evaluator;

use Owebia\SharedPhpConfig\Helper\Config;
use Owebia\SharedPhpConfig\Helper\Evaluator;
use Owebia\SharedPhpConfig\Helper\Registry;
use Owebia\SharedPhpConfig\Model\CallbackHandler;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $config = '';

    /**
     * @var \Owebia\SharedPhpConfig\Helper\Config
     */
    protected $configHelper;

    /**
     * @var \Owebia\SharedPhpConfig\Helper\Registry
     */
    protected $registry;

    /**
     * @var \Owebia\SharedPhpConfig\Model\CallbackHandler
     */
    protected $callbackHandler;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $arguments = [
            'evaluator' => $this->objectManager->getObject(Evaluator::class),
        ];
        $this->configHelper = $this->objectManager->getObject(Config::class, $arguments);
        $this->callbackHandler = $this->objectManager->getObject(CallbackHandler::class);
        $this->init();
    }

    /**
     * @return $this
     */
    protected function init()
    {
        $this->registry = $this->objectManager->getObject(Registry::class);
        $this->config = '';
        return $this;
    }

    /**
     * @param string $configuration
     * @return $this
     */
    protected function parse($configuration)
    {
        $this->init();
        $this->append($configuration);
        return $this;
    }

    /**
     * @param string $configuration
     * @return $this
     */
    protected function append($configuration)
    {
        $this->config .= $configuration . "\n";
        $this->configHelper->parse(
            $configuration,
            $this->registry,
            $this->callbackHandler
        );
        return $this;
    }

    /**
     * @return $this
     */
    protected function dump()
    {
        print_r("\n" . $this->config);
        print_r($this->registry->getData());
        return $this;
    }

    /**
     * @param string $variableName
     * @param mixed $value
     * @return $this
     */
    protected function assertVariableSame($variableName, $value)
    {
        $this->assertSame(
            $this->registry->getGlobal(ltrim($variableName, '$')),
            $value
        );
        return $this;
    }
}
