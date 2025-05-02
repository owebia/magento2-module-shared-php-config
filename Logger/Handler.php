<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Logger;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/owebia_advancedsettingcore.log';

    /**
     * setFormatter is called in the constructor of Base
     * Overriding this method is the easier way to use a custom formatter without a lot of code to maintain
     *
     * @param FormatterInterface $formatter
     */
    #[\Override]
    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        return parent::setFormatter(new LineFormatter("%message%\n"));
    }
}
