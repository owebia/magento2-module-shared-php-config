<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Api;

use Owebia\SharedPhpConfig\Api\RegistryInterface;

interface ParserContextInterface
{
    /**
     * @return FunctionProxyInterface
     */
    public function getFunctionProxy(): FunctionProxyInterface;

    /**
     * @return RegistryInterface
     */
    public function getRegistry(): RegistryInterface;

    /**
     * @param string $configuration
     * @param bool $debug
     * @param array $data
     * @return array
     */
    public function parse(string $configuration, bool $debug, array $data = []): array;

    /**
     * @param string $error
     */
    public function addParsingError(string $error): void;
}
