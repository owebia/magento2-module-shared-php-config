<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model;

use BadMethodCallException;
use Owebia\SharedPhpConfig\Api\ParserContextInterface;
use Owebia\SharedPhpConfig\Api\FunctionProviderInterface;
use Owebia\SharedPhpConfig\Api\FunctionProxyInterface;
use Owebia\SharedPhpConfig\Api\RequiresParserContextInterface;

class FunctionProxy implements FunctionProxyInterface, RequiresParserContextInterface
{
    /**
     * @var ParserContextInterface
     */
    private $parserContext;

    /**
     * @var FunctionProviderInterface[]
     */
    private $functionProviders;

    /**
     * @var array
     */
    private $methodMap = [];

    /**
     * @param FunctionProviderInterface[] $functionProviders
     */
    public function __construct(
        array $functionProviders
    ) {
        $this->functionProviders = [];
        foreach ($functionProviders as $functionProvider) {
            $this->registerFunctionProvider($functionProvider);
        }
    }

    /**
     * @param ParserContextInterface $parserContext
     * @return $this
     */
    public function setContext(ParserContextInterface $parserContext)
    {
        $this->parserContext = $parserContext;
        foreach ($this->functionProviders as $functionProvider) {
            if ($functionProvider instanceof RequiresParserContextInterface) {
                $functionProvider->setContext($parserContext);
            }
        }
        return $this;
    }

    /**
     * @return ParserContextInterface
     */
    public function getContext(): ParserContextInterface
    {
        return $this->parserContext;
    }

    /**
     * @param string $functionName
     * @return bool
     */
    public function functionExists(string $functionName): bool
    {
        return isset($this->methodMap[$functionName]);
    }

    /**
     * @param FunctionProviderInterface $functionProvider
     */
    public function registerFunctionProvider(FunctionProviderInterface $functionProvider): void
    {
        $this->functionProviders[] = $functionProvider;
        foreach ($functionProvider->getFunctionMap() as $functionName => $methodName) {
            $this->registerFunction($functionName, $functionProvider, $methodName);
        }
    }

    /**
     * @param string $functionName
     * @param array $arguments
     * @return mixed
     */
    public function __call($functionName, $arguments)
    {
        if (isset($this->methodMap[$functionName])) {
            list($functionProvider, $methodName) = $this->methodMap[$functionName];
            if (method_exists($functionProvider, $methodName) || method_exists($functionProvider, '__call')) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                return call_user_func_array([$functionProvider, $methodName], $arguments);
            }
        }
        throw new BadMethodCallException("Function $functionName not found");
    }

    /**
     * @param string $functionName
     * @param FunctionProviderInterface $functionProvider
     * @param string $methodName
     */
    private function registerFunction(
        string $functionName,
        FunctionProviderInterface $functionProvider,
        string $methodName
    ): void {
        $this->methodMap[$functionName] = [$functionProvider, $methodName];
    }
}
