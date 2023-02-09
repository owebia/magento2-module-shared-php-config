<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Api;

use Owebia\SharedPhpConfig\Api\FunctionProviderInterface;

interface FunctionProviderPoolInterface
{
    /**
     * @param string $functionName
     * @return bool
     */
    public function functionExists(string $functionName): bool;

    /**
     * @param FunctionProviderInterface $functionProvider
     * @param string|null $name
     */
    public function add(FunctionProviderInterface $functionProvider, ?string $name = null): void;

    /**
     * @param string $functionName
     * @param array $arguments
     * @return mixed
     */
    public function call(string $functionName, array $arguments);
}
