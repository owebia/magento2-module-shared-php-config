<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Test\Unit\Model\Evaluator;

use Magento\Framework\ObjectManagerInterface;
use Owebia\SharedPhpConfig\Model\Evaluator;
use Owebia\SharedPhpConfig\Model\FunctionProviderPool;
use Owebia\SharedPhpConfig\Model\Parser;
use Owebia\SharedPhpConfig\Model\ParserContext;
use Owebia\SharedPhpConfig\Model\Registry;
use Owebia\SharedPhpConfig\Model\Wrapper\SourceWrapper;
use Owebia\SharedPhpConfig\Model\WrapperContext;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
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
     * @var Parser
     */
    protected Parser $parser;

    /**
     * @var ParserContext
     */
    protected ParserContext $parserContext;

    protected function setUp(): void
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->init();
    }

    /**
     * @return $this
     */
    protected function init(): self
    {
        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'get', 'configure'])
            ->getMock();
        $wrapperContext = $this->objectManager->getObject(WrapperContext::class, [
            'objectManager' => $objectManager,
        ]);
        $objectManager->expects($this->any())
            ->method('create')
            ->will($this->returnCallback(function ($type, $args) use ($wrapperContext) {
                try {
                    $args['wrapperContext'] = $wrapperContext;
                    $res = $this->objectManager->getObject(SourceWrapper::class, $args);
                } catch (\Exception $e) {
                    //echo $e->getMessage() . "\n";
                    //echo $e->getTraceAsString();
                }
                return $res;
            }));
        $this->parserContext = $this->objectManager->getObject(ParserContext::class, [
            'wrapperContext' => $wrapperContext,
            'functionProviderPool' => $this->objectManager->getObject(FunctionProviderPool::class),
            'registry' => $this->objectManager->getObject(Registry::class),
        ]);
        $this->parser = $this->objectManager->getObject(Parser::class, [
            'parserContext' => $this->parserContext,
            'evaluator' => $this->objectManager->getObject(Evaluator::class, ['wrapperContext' => $wrapperContext]),
            'phpParserFactory' => $this->objectManager->getObject(\PhpParser\ParserFactory::class),
        ]);
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
