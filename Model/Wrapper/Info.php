<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Framework\HTTP\PhpEnvironment\Request;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class Info extends ArrayWrapper
{
    /**
     * @var string[]
     */
    protected array $additionalAttributes = ['memory_limit', 'memory_usage'];

    /**
     * @param Request $request
     * @param WrapperContext $wrapperContext
     * @param string $carrierCode
     */
    public function __construct(
        Request $request,
        WrapperContext $wrapperContext,
        $carrierCode = null
    ) {
        parent::__construct($wrapperContext, [
            'server_os'       => PHP_OS,
            'server_software' => $request->getServerValue('SERVER_SOFTWARE'),
            'php_version'     => PHP_VERSION,
            'carrier_code'    => $carrierCode
        ]);
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function loadData(string $key)
    {
        switch ($key) {
            case 'memory_limit':
                return ini_get('memory_limit');
            case 'memory_usage':
                return memory_get_usage(true);
        }
        return parent::loadData($key);
    }
}
