<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model;

use Owebia\SharedPhpConfig\Api\ParserContextInterface;
use Owebia\SharedPhpConfig\Api\FunctionProviderInterface;
use Owebia\SharedPhpConfig\Api\FunctionProviderPoolInterface;
use Owebia\SharedPhpConfig\Api\RegistryInterface;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class ParserContext implements ParserContextInterface
{
    /**
     * @var WrapperContext
     */
    private WrapperContext $wrapperContext;

    /**
     * @var FunctionProviderPoolInterface
     */
    private FunctionProviderPoolInterface $functionProviderPool;

    /**
     * @var FunctionProviderInterface
     */
    private FunctionProviderInterface $mainFunctionProvider;

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var string
     */
    private string $debugPrefix;

    /**
     * @var bool
     */
    private bool $debug;

    /**
     * @param WrapperContext $wrapperContext
     * @param RegistryInterface $registry
     * @param FunctionProviderPoolInterface $functionProviderPool
     * @param FunctionProviderInterface|null $mainFunctionProvider
     * @param string $debugPrefix
     * @param bool $debug
     */
    public function __construct(
        WrapperContext $wrapperContext,
        RegistryInterface $registry,
        FunctionProviderPoolInterface $functionProviderPool,
        ?FunctionProviderInterface $mainFunctionProvider = null,
        string $debugPrefix = '',
        bool $debug = false
    ) {
        $this->wrapperContext = $wrapperContext;
        $this->functionProviderPool = $functionProviderPool;
        $this->registry = $registry;
        $this->debugPrefix = $debugPrefix;
        $this->debug = $debug;
        $this->functionProviderPool->setParserContext($this);
        if ($mainFunctionProvider) {
            $this->functionProviderPool->add($mainFunctionProvider);
        }
    }

    /**
     * @return WrapperContext
     */
    public function getWrapperContext(): WrapperContext
    {
        return $this->wrapperContext;
    }

    /**
     * @return FunctionProviderPoolInterface
     */
    public function getFunctionProviderPool(): FunctionProviderPoolInterface
    {
        return $this->functionProviderPool;
    }

    /**
     * @return RegistryInterface
     */
    public function getRegistry(): RegistryInterface
    {
        return $this->registry ??= $this->registryFactory->create();
    }

    /**
     * @return string
     */
    public function getDebugPrefix(): string
    {
        return $this->debugPrefix;
    }

    /**
     * @return bool
     */
    public function getDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param string $error
     */
    public function addError(string $error): void
    {
        $this->errors[] = $error;
        if ($this->functionProviderPool->functionExists('addError')) {
            $this->functionProviderPool->call('addError', [$error]);
        }
    }
}
