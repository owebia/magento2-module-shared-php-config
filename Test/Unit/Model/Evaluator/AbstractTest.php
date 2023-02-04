<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Owebia\SharedPhpConfig\Test\Unit\Model\Evaluator;

use Magento\Framework\ObjectManagerInterface;
use Owebia\SharedPhpConfig\Model\Evaluator;
use Owebia\SharedPhpConfig\Model\FunctionProxy;
use Owebia\SharedPhpConfig\Model\Parser;
use Owebia\SharedPhpConfig\Model\Registry;
use Owebia\SharedPhpConfig\Model\Wrapper\SourceWrapper;
use Owebia\SharedPhpConfig\Model\WrapperContext;
use Owebia\SharedPhpConfig\Test\Unit\Model\Evaluator\ParserContext;
use PHPUnit\Framework\TestCase;

abstract class AbstractTest extends TestCase
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
     * @var \Owebia\SharedPhpConfig\Model\Parser
     */
    protected $parser;

    /**
     * @var \Owebia\SharedPhpConfig\Model\ParserContext
     */
    protected $parserContext;

    protected function setUp(): void
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->init();
    }

    /**
     * @return $this
     */
    protected function init()
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
        $registry = $this->objectManager->getObject(Registry::class, [
            'wrapperContext' => $wrapperContext,
        ]);
        $functionProxy = $this->objectManager->getObject(FunctionProxy::class, [
            'functionProviders' => [],
        ]);
        $this->parserContext = $this->objectManager->getObject(ParserContext::class, [
            'wrapperContext' => $wrapperContext,
            'functionProxy' => $functionProxy,
            'registry' => $registry,
        ]);
        $evaluatorFactory = $this->getMockBuilder(Evaluator::class . 'Factory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $evaluatorFactory->expects($this->any())
            ->method('create')
            ->will($this->returnCallback(fn($args) => $this->objectManager->getObject(Evaluator::class, $args + [
                'wrapperContext' => $wrapperContext,
                'functionProxy' => $functionProxy,
                'registry' => $registry,
            ])));
        $this->parser = $this->objectManager->getObject(Parser::class, [
            'parserContext' => $this->parserContext,
            'evaluatorFactory' => $evaluatorFactory,
            'phpParserFactory' => $this->objectManager->getObject(\PhpParser\ParserFactory::class),
        ]);
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
        $this->parser->parse($configuration, false);
        return $this;
    }

    /**
     * @return $this
     */
    protected function dump()
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
    protected function assertVariableSame($variableName, $value)
    {
        $this->assertSame(
            $this->parserContext->getRegistry()->getGlobal(ltrim($variableName, '$')),
            $value
        );
        return $this;
    }
}
