<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Api;

use Owebia\SharedPhpConfig\Api\FunctionProviderInterface;

interface FunctionProxyInterface
{
    /**
     * @param string $functionName
     * @return bool
     */
    public function functionExists(string $functionName): bool;

    /**
     * @param FunctionProviderInterface $functionProvider
     */
    public function registerFunctionProvider(FunctionProviderInterface $functionProvider): void;

    /**
     * @param string $functionName
     * @param array $arguments
     * @return mixed
     */
    public function __call($functionName, $arguments);
}
