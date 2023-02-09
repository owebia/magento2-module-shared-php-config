<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Api;

interface ParserInterface
{
    /**
     * @param ParserContextInterface $context
     * @param string $configuration
     */
    public function parse(ParserContextInterface $context, string $configuration): void;
}
