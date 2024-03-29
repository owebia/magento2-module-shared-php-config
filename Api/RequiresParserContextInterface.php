<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Api;

interface RequiresParserContextInterface
{
    /**
     * @param ParserContextInterface $parserContext
     */
    public function setParserContext(ParserContextInterface $parserContext): void;
}
