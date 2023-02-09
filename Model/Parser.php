<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model;

use Magento\Framework\Escaper;
use Owebia\SharedPhpConfig\Api\ParserContextInterface;
use Owebia\SharedPhpConfig\Api\ParserInterface;
use Owebia\SharedPhpConfig\Logger\ParserDebugLogger;
use Owebia\SharedPhpConfig\Model\Evaluator;
use Owebia\SharedPhpConfig\Model\EvaluatorFactory;
use PhpParser\ParserFactory as PhpParserFactory;
use PhpParser\Node\Stmt\Nop;
use Psr\Log\LoggerInterface;

class Parser implements ParserInterface
{
    /**
     * @var array
     */
    private array $parsingCache = [];

    /**
     * @var Escaper
     */
    private Escaper $escaper;

    /**
     * @var Evaluator
     */
    private Evaluator $evaluator;

    /**
     * @var PhpParserFactory
     */
    private PhpParserFactory $phpParserFactory;

    /**
     * @var ParserDebugLogger
     */
    private ParserDebugLogger $debugLogger;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Escaper $escaper
     * @param Evaluator $evaluator
     * @param PhpParserFactory $phpParserFactory
     * @param ParserDebugLogger $debugLogger
     * @param LoggerInterface $logger
     */
    public function __construct(
        Escaper $escaper,
        Evaluator $evaluator,
        PhpParserFactory $phpParserFactory,
        ParserDebugLogger $debugLogger,
        LoggerInterface $logger
    ) {
        $this->escaper = $escaper;
        $this->evaluator = $evaluator;
        $this->phpParserFactory = $phpParserFactory;
        $this->debugLogger = $debugLogger;
        $this->logger = $logger;
    }

    /**
     * @param ParserContextInterface $context
     * @param string $configuration
     */
    public function parse(ParserContextInterface $context, string $configuration): void
    {
        $context->getDebug() && $this->debugLogger->collapseOpen($context->getDebugPrefix());

        $t0 = microtime(true);
        try {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            ini_set('xdebug.max_nesting_level', '3000');

            $phpParser = $this->phpParserFactory->create(PhpParserFactory::PREFER_PHP7);

            $hash = hash('md5', $configuration);
            if (!isset($this->parsingCache[$hash])) {
                // $stmts is an array of statement nodes
                $stmts = $phpParser->parse("<?php " . $configuration . ";");
                $this->parsingCache[$hash] = $stmts;
            } else {
                $stmts = $this->parsingCache[$hash];
            }

            foreach ($stmts ?? [] as $node) {
                if ($node instanceof Nop) {
                    continue;
                }

                $this->parseNode($context, $this->evaluator, $node);
                $this->evaluator->reset();
            }
        } catch (\Exception $e) {
            $this->logger->debug($e);
            $context->getDebug()
                && $this->debugLogger->debug($context->getDebugPrefix() . " - Error - " . $e->getMessage());
        }

        $t1 = microtime(true);
        $context->getDebug() && $this->debugLogger->debug("Duration " . round($t1 - $t0, 2) . " s");
        $context->getDebug() && $this->debugLogger->collapseClose();
    }

    /**
     * @param ParserContextInterface $context
     * @param Evaluator $evaluator
     * @param object $node
     * @throws \Exception
     */
    private function parseNode(ParserContextInterface $context, Evaluator $evaluator, $node): void
    {
        try {
            $evaluator->evaluate($context, $node);
            if ($context->getDebug()) {
                $msg = $evaluator->getDebugOutput();
                $this->addDebug($evaluator, $node, $msg, 'panel-info');
            }
        } catch (\Exception $e) {
            $context->addError('Error ' . $e->getTraceAsString());
            if ($context->getDebug()) {
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
