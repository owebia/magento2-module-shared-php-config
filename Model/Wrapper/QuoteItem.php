<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Catalog\Helper\Product\Configuration as Configuration;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class QuoteItem extends SourceWrapper
{
    /**
     * @var string[]
     */
    protected array $additionalAttributes = ['options'];

    /**
     * @var Configuration
     */
    private Configuration $productConfig;

    /**
     * @param Configuration $productConfig
     * @param WrapperContext $wrapperContext
     * @param mixed $data
     */
    public function __construct(
        Configuration $productConfig,
        WrapperContext $wrapperContext,
        $data = null
    ) {
        $this->productConfig = $productConfig;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function loadData(string $key)
    {
        switch ($key) {
            case 'options':
                $options = [];
                $customOptions = $this->productConfig->getCustomOptions($this->getSource());
                if ($customOptions) {
                    foreach ($customOptions as $option) {
                        $options[$option['label']] = $option;
                    }
                }

                return $options;
        }

        return parent::loadData($key);
    }
}
