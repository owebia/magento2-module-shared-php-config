<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model;

use BadMethodCallException;
use Owebia\SharedPhpConfig\Api\FunctionProviderInterface;
use Owebia\SharedPhpConfig\Api\FunctionProviderPoolInterface;
use Owebia\SharedPhpConfig\Api\ParserContextInterface;
use Owebia\SharedPhpConfig\Api\RequiresParserContextInterface;

class FunctionProviderPool implements FunctionProviderPoolInterface, RequiresParserContextInterface
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
        array $functionProviders = []
    ) {
        $this->functionProviders = $functionProviders;
        foreach ($functionProviders as $name => $functionProvider) {
            $this->add($functionProvider, $name);
        }
    }

    /**
     * @param ParserContextInterface $parserContext
     */
    public function setParserContext(ParserContextInterface $parserContext): void
    {
        $this->parserContext = $parserContext;
        foreach ($this->functionProviders as $functionProvider) {
            if ($functionProvider instanceof RequiresParserContextInterface) {
                $functionProvider->setParserContext($parserContext);
            }
        }
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
     * @param string|null $name
     */
    public function add(FunctionProviderInterface $functionProvider, ?string $name = null): void
    {
        $this->functionProviders[$name ?? count($this->functionProviders)] = $functionProvider;
        foreach ($functionProvider->getFunctions() as $functionName => $methodName) {
            $this->registerFunction(
                is_int($functionName) ? $methodName : $functionName,
                $functionProvider,
                $methodName
            );
        }
        if (isset($this->parserContext) && $functionProvider instanceof RequiresParserContextInterface) {
            $functionProvider->setParserContext($this->parserContext);
        }
    }

    /**
     * @param string $functionName
     * @param array $arguments
     * @return mixed
     */
    public function call(string $functionName, array $arguments)
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
