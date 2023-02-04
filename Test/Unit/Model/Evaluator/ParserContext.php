<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Test\Unit\Model\Evaluator;

use Owebia\SharedPhpConfig\Model\Parser;

class ParserContext extends \Owebia\SharedPhpConfig\Model\ParserContext
{
    /**
     * @return string[]
     */
    public function getFunctionMap(): array
    {
        return [];
    }

    /**
     * @param string $configuration
     * @param bool $debug
     * @return array
     */
    protected function doParse(string $configuration, bool $debug): array
    {
        /** @var Parser $parser */
        $parser = $this->getParserFactory()->create(['parserContext' => $this]);
        $parser->parse($configuration, $debug);

        return [];
    }

    /**
     * @param string $error
     */
    public function addParsingError(string $error): void
    {
        // do nothing
    }
}
