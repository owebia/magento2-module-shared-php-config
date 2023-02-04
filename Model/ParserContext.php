<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Owebia\SharedPhpConfig\Api\ParserContextInterface;
use Owebia\SharedPhpConfig\Api\FunctionProviderInterface;
use Owebia\SharedPhpConfig\Api\FunctionProxyInterface;
use Owebia\SharedPhpConfig\Api\RegistryInterface;
use Owebia\SharedPhpConfig\Api\RegistryInterfaceFactory;
use Owebia\SharedPhpConfig\Logger\Logger;
use Owebia\SharedPhpConfig\Model\ParserFactory;
use Owebia\SharedPhpConfig\Model\Wrapper;
use Owebia\SharedPhpConfig\Model\WrapperContext;
use Psr\Log\LoggerInterface;

abstract class ParserContext implements ParserContextInterface, FunctionProviderInterface
{
    /**
     * @var ParserFactory
     */
    private $parserFactory;

    /**
     * @var RegistryInterfaceFactory
     */
    private $registryFactory;

    /**
     * @var RateRequest
     */
    private $request;

    /**
     * @var FunctionProxyInterface
     */
    private $functionProxy;

    /**
     * @var WrapperContext
     */
    private $wrapperContext;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Logger
     */
    private $debugLogger;

    /**
     * @var string
     */
    private $debugPrefix;

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @param FunctionProxyInterface $functionProxy
     * @param ParserFactory $parserFactory
     * @param RegistryInterfaceFactory $registryFactory
     * @param WrapperContext $wrapperContext
     * @param LoggerInterface $logger
     * @param Logger $debugLogger
     * @param RateRequest|null $request
     * @param string $debugPrefix
     */
    public function __construct(
        FunctionProxyInterface $functionProxy,
        ParserFactory $parserFactory,
        RegistryInterfaceFactory $registryFactory,
        WrapperContext $wrapperContext,
        LoggerInterface $logger,
        Logger $debugLogger,
        RateRequest $request = null,
        string $debugPrefix = ''
    ) {
        $this->functionProxy = $functionProxy;
        $this->parserFactory = $parserFactory;
        $this->registryFactory = $registryFactory;
        $this->wrapperContext = $wrapperContext;
        $this->logger = $logger;
        $this->debugLogger = $debugLogger;
        $this->request = $request;
        $this->debugPrefix = $debugPrefix;
    }

    /**
     * @return ParserFactory
     */
    public function getParserFactory(): ParserFactory
    {
        return $this->parserFactory;
    }

    /**
     * @return WrapperContext
     */
    public function getWrapperContext(): WrapperContext
    {
        return $this->wrapperContext;
    }

    /**
     * @return FunctionProxyInterface
     */
    public function getFunctionProxy(): FunctionProxyInterface
    {
        return $this->functionProxy;
    }

    /**
     * @return RegistryInterface
     */
    public function getRegistry(): RegistryInterface
    {
        return $this->registry;
    }

    /**
     * @param string $configuration
     * @param bool $debug
     * @param array $data
     * @return array
     */
    public function parse(string $configuration, bool $debug, array $data = []): array
    {
        if ($debug) {
            $this->debugLogger->collapseOpen($this->debugPrefix, 'panel-primary');
        }

        $this->functionProxy->registerFunctionProvider($this);

        /** @var RegistryInterface $registry */
        $registry = $this->registry = $this->registryFactory->create();
        $registry->init($this->request);
        $registry->register(
            'info',
            $this->wrapperContext->createWrapper(Wrapper\Info::class, ['data' => $data])
        );

        $this->functionProxy->setContext($this);

        try {
            $result = $this->doParse($configuration, $debug);
        } catch (\Exception $e) {
            $result = [];
            $this->logger->debug($e);
            if ($debug) {
                $this->debugLogger->debug($this->debugPrefix . " - Error - " . $e->getMessage());
            }
        }

        if ($debug) {
            $this->debugLogger->collapseClose();
        }

        return $result;
    }

    /**
     * @param string $configuration
     * @param bool $debug
     * @return array
     */
    abstract protected function doParse(string $configuration, bool $debug): array;

    /**
     * @param string $error
     */
    abstract public function addParsingError(string $error): void;
}
