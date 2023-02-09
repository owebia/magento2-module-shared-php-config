<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\FunctionProvider;

use Owebia\SharedPhpConfig\Api\FunctionProviderInterface;

class NativePhpFunctionProvider implements FunctionProviderInterface
{
    /**
     * @var string[]
     */
    private $allowedFunctions = [
        // Math Functions
        'abs',
        'ceil',
        'floor',
        'max',
        'min',
        'round',
        // String Functions
        'explode',
        'implode',
        'strlen',
        'strpos',
        'strtolower',
        'strtoupper',
        'substr',
        // Multibyte String Functions
        'mb_strlen',
        'mb_strpos',
        'mb_strtolower',
        'mb_strtoupper',
        'mb_substr',
        // PCRE Functions
        'preg_match',
        'preg_replace',
        // Date/Time Functions
        'date',
        'strtotime',
        'time',
        // Array Functions
        'array_filter',
        'array_intersect',
        'array_key_exists',
        'array_keys',
        'array_map',
        'array_reduce',
        'array_search',
        'array_sum',
        'array_unique',
        'array_values',
        'count',
        'in_array',
        'range',
        // JSON Functions
        'json_decode',
        'json_encode',
    ];

    /**
     * @return string[]
     */
    public function getFunctions(): array
    {
        return $this->allowedFunctions;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        return call_user_func_array($name, $arguments);
    }
}
