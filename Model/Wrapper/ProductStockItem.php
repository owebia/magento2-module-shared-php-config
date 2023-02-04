<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class ProductStockItem extends SourceWrapper
{
    /**
     * @var StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param WrapperContext $wrapperContext
     * @param mixed $data
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        WrapperContext $wrapperContext,
        $data = null
    ) {
        $this->stockRegistry = $stockRegistry;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @return StockItemInterface|null
     */
    protected function loadSource(): ?object
    {
        return $this->stockRegistry->getStockItem($this->data['product_id']);
    }
}
