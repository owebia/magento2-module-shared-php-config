<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Api;

use Owebia\SharedPhpConfig\Api\FunctionProviderPoolInterface;
use Owebia\SharedPhpConfig\Api\RegistryInterface;

interface ParserContextInterface
{
    /**
     * @return FunctionProviderPoolInterface
     */
    public function getFunctionProviderPool(): FunctionProviderPoolInterface;

    /**
     * @return RegistryInterface
     */
    public function getRegistry(): RegistryInterface;

    /**
     * @return string
     */
    public function getDebugPrefix(): string;

    /**
     * @return bool
     */
    public function getDebug(): bool;
}
