<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Owebia\SharedPhpConfig\Model\Wrapper;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class Product extends SourceWrapper
{
    /**
     * @var string[]
     */
    protected array $additionalAttributes = [
        'attribute_set',
        'stock_item',
        'category_id',
        'category',
        'category_ids',
        'categories',
    ];

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var array
     */
    protected array $attributes = [];

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param WrapperContext $wrapperContext
     * @param mixed $data
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        WrapperContext $wrapperContext,
        $data = null
    ) {
        $this->productRepository = $productRepository;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @return ProductInterface|null
     */
    protected function loadSource(): ?object
    {
        if ($this->data instanceof ProductInterface) {
            return $this->data;
        }
        return $this->productRepository->getById($this->data['id']);
    }

    /**
     * Load source model
     *
     * @return $this
     */
    public function load()
    {
        $this->source = $this->productRepository->getById($this->entity_id);
        $this->cache->setData([]);
        return $this;
    }

    /**
     * @param string $attributeCode
     * @return $this
     */
    protected function loadIfRequired(string $attributeCode)
    {
        if (!isset($this->attributes)) {
            $source = $this->getSource();
            $this->attributes = $source->getAttributes();
        }
        // If attribute data is not loaded, load it
        if (isset($this->attributes[$attributeCode]) && !$this->getSource()->hasData($attributeCode)) {
            $this->load();
        }
        return $this;
    }

    /**
     * @param string $attributeCode
     * @return string|array|null
     */
    public function getAttributeText($attributeCode)
    {
        $this->loadIfRequired($attributeCode);
        /** @var \Magento\Catalog\Api\Data\Product $product */
        $product = $this->getSource();
        return $this->wrapperContext->wrap($product->getAttributeText($attributeCode));
    }

    /**
     * @return \Magento\InventoryApi\Api\Data\SourceItemInterface[]
     */
    public function getSourceItems(): array
    {
        /** @var ProductInterface $product */
        $product = $this->getSource();
        return $this->wrapperContext->wrap(
            $this->wrapperContext->get(GetSourceItemsBySkuInterface::class)
                ->execute(
                    $product->getSku()
                )
        );
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function loadData(string $key)
    {
        switch ($key) {
            case 'attribute_set':
                return $this->wrapperContext->create(
                    Wrapper\ProductAttributeSet::class,
                    ['data' => ['id' => (int)$this->{'attribute_set_id'}]]
                );
            case 'stock_item':
                return $this->wrapperContext->create(
                    Wrapper\ProductStockItem::class,
                    ['data' => ['product_id' => (int)$this->{'entity_id'}]]
                );
            case 'category_id':
                return $this->category_ids[0] ?? null;
            case 'category':
                return $this->categories[0] ?? null;
            case 'category_ids':
                /** @var ProductInterface $product */
                $product = $this->getSource();
                return $this->getSource()->getCategoryIds();
            case 'categories':
                /** @var ProductInterface $product */
                $product = $this->getSource();
                $categories = [];
                $collection = $product->getCategoryCollection()
                    ->addAttributeToSelect('name');
                foreach ($collection as $category) {
                    $categories[] = $category;
                }
                return $categories;
            default:
                $this->loadIfRequired($key);
                return parent::loadData($key);
        }
    }
}
