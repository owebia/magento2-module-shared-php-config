<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Test\Unit\Model\Evaluator;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\ObjectManagerInterface;
use Owebia\SharedPhpConfig\Logger\ParserDebugLogger;
use Owebia\SharedPhpConfig\Model\Evaluator;
use Owebia\SharedPhpConfig\Model\FunctionProviderPool;
use Owebia\SharedPhpConfig\Model\Parser;
use Owebia\SharedPhpConfig\Model\ParserContext;
use Owebia\SharedPhpConfig\Model\Registry;
use Owebia\SharedPhpConfig\Model\Wrapper\SourceWrapper;
use Owebia\SharedPhpConfig\Model\WrapperContext;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

abstract class AbstractTestCase extends TestCase
{
    /**
     * @var string
     */
    protected $config = '';

    /**
     * @var Parser
     */
    protected Parser $parser;

    /**
     * @var ParserContext
     */
    protected ParserContext $parserContext;

    protected function setUp(): void
    {
        $this->init();
    }

    /**
     * @return $this
     */
    protected function init(): self
    {
        $objectManager = $this->createStub(ObjectManagerInterface::class);
        $escaper = $this->createStub(Escaper::class);
        $wrapperContext = new WrapperContext($escaper, $objectManager, $this->createStub(Session::class));
        $objectManager->method('create')
            ->willReturnCallback(function ($type, $args = []) use ($wrapperContext) {
                if ($type === DataObject::class) {
                    return new DataObject($args['data'] ?? []);
                }
                if ($type === SourceWrapper::class) {
                    return new SourceWrapper($wrapperContext, $args['data'] ?? null);
                }
                throw new \InvalidArgumentException('Unsupported fixture type: ' . $type);
            });
        $this->parserContext = new ParserContext($wrapperContext, new Registry(), new FunctionProviderPool());
        $this->parser = new Parser(
            $escaper,
            new Evaluator($escaper, $wrapperContext),
            new ParserFactory(),
            $this->createStub(ParserDebugLogger::class),
            $this->createStub(LoggerInterface::class)
        );
        $this->config = '';
        return $this;
    }

    /**
     * @param string $configuration
     * @return $this
     */
    protected function parse(string $configuration): self
    {
        $this->init();
        $this->append($configuration);
        return $this;
    }

    /**
     * @param string $configuration
     * @return $this
     */
    protected function append(string $configuration): self
    {
        $this->config .= $configuration . "\n";
        $this->parser->parse($this->parserContext, $configuration);
        return $this;
    }

    /**
     * @return $this
     */
    protected function dump(): self
    {
        print_r("\n" . $this->config);
        print_r($this->parserContext->getRegistry());
        return $this;
    }

    /**
     * @param string $variableName
     * @param mixed $value
     * @return $this
     */
    protected function assertVariableSame(string $variableName, $value): self
    {
        $this->assertSame(
            $this->parserContext->getRegistry()->getGlobal(ltrim($variableName, '$')),
            $value
        );
        return $this;
    }
}
