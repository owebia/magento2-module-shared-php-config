<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\FunctionProvider;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Owebia\SharedPhpConfig\Api\FunctionProviderInterface;

class BaseFunctionProvider implements FunctionProviderInterface
{
    /**
     * @return string[]
     */
    public function getFunctions(): array
    {
        return [
            '__' => 'translate',
            'help',
            'error',
        ];
    }

    /**
     * @param mixed $args,...
     * @return string
     */
    public function translate(/* ...$args */): string
    {
        $args = func_get_args();
        $text = array_shift($args);
        return (string)new Phrase($text, $args);
    }

    /**
     * @return string
     */
    public function help(): string
    {
        return "The result of the help call is visible in the backoffice";
    }

    /**
     * @param string $msg
     * @throws LocalizedException
     */
    public function error(string $msg): void
    {
        throw new LocalizedException(__($msg));
    }
}
