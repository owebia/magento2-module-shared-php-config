<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model;

use Magento\Framework\Escaper;
use Owebia\SharedPhpConfig\Api\ParserContextInterface;
use Owebia\SharedPhpConfig\Logger\Logger;
use Owebia\SharedPhpConfig\Model\Evaluator;
use Owebia\SharedPhpConfig\Model\EvaluatorFactory;
use PhpParser\ParserFactory as PhpParserFactory;
use PhpParser\Node\Stmt\Nop;

class Parser
{
    /**
     * @var array
     */
    private $parsingCache = [];

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var ParserContextInterface
     */
    private $parserContext;

    /**
     * @var EvaluatorFactory
     */
    private $evaluatorFactory;

    /**
     * @var Logger
     */
    private $debugLogger;

    /**
     * @param Escaper $escaper
     * @param ParserContextInterface $parserContext
     * @param Logger $debugLogger
     * @param EvaluatorFactory $evaluatorFactory
     * @param PhpParserFactory $phpParserFactory
     */
    public function __construct(
        Escaper $escaper,
        ParserContextInterface $parserContext,
        Logger $debugLogger,
        EvaluatorFactory $evaluatorFactory,
        PhpParserFactory $phpParserFactory
    ) {
        $this->escaper = $escaper;
        $this->parserContext = $parserContext;
        $this->debugLogger = $debugLogger;
        $this->evaluatorFactory = $evaluatorFactory;
        $this->phpParserFactory = $phpParserFactory;
    }

    /**
     * @param string $configuration
     * @param bool $debug
     */
    public function parse(string $configuration, bool $debug = false): void
    {
        $t0 = microtime(true);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        ini_set('xdebug.max_nesting_level', '3000');

        $parser = $this->phpParserFactory->create(PhpParserFactory::PREFER_PHP7);

        $hash = hash('md5', $configuration);
        if (!isset($this->parsingCache[$hash])) {
            // $stmts is an array of statement nodes
            $stmts = $parser->parse("<?php " . $configuration . ";");
            $this->parsingCache[$hash] = $stmts;
        } else {
            $stmts = $this->parsingCache[$hash];
        }

        /** @var Evaluator $evaluator */
        $evaluator = $this->evaluatorFactory->create([
            'registry' => $this->parserContext->getRegistry(),
            'functionProxy' => $this->parserContext->getFunctionProxy(),
            'debug' => $debug,
        ]);

        foreach ($stmts as $node) {
            if ($node instanceof Nop) {
                continue;
            }

            $this->parseNode($evaluator, $node, $debug);
            $evaluator->reset();
        }
        $t1 = microtime(true);
        if ($debug) {
            $this->debugLogger->debug("Duration " . round($t1 - $t0, 2) . " s");
        }
    }

    /**
     * @param Evaluator $evaluator
     * @param object $node
     * @param bool $debug
     * @throws \Exception
     */
    private function parseNode(Evaluator $evaluator, $node, bool $debug): void
    {
        try {
            $evaluator->evaluate($node);
            if ($debug) {
                $msg = $evaluator->getDebugOutput();
                $this->addDebug($evaluator, $node, $msg, 'panel-info');
            }
        } catch (\Exception $e) {
            $this->parserContext->addParsingError('Error ' . $e->getTraceAsString());
            if ($debug) {
                $msg = $evaluator->getDebugOutput() . $e->getMessage();
                $this->addDebug($evaluator, $node, $msg, 'panel-danger');
            }
        }
    }

    /**
     * @param Evaluator $evaluator
     * @param mixed $node
     * @param string $msg
     * @param string $panel
     */
    private function addDebug(Evaluator $evaluator, $node, string $msg, string $panel): void
    {
        $title = $evaluator->prettyPrint($node);
        $this->debugLogger->collapse(
            "<pre class=php>" . $this->escaper->escapeHtml($title) . "</pre>",
            $msg,
            $panel
        );
    }
}
