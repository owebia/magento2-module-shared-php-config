<?php
/**
 * Copyright © 2016 Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Owebia\ShippingCore\Model\Wrapper;

class Product extends SourceWrapper
{
    /**
     * @var array
     */
    protected $additionalAttributes = [
        'attribute_set', 'stock_item',
        'category_id', 'category', 'category_ids', 'categories',
    ];

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRespository;

    /**
     * @param \Magento\Catalog\Model\ProductRepository $productRespository
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @param \Owebia\ShippingCore\Helper\Registry $registry
     * @param mixed $data
     */
    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRespository,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Quote\Model\Quote\Address\RateRequest $request,
        \Owebia\ShippingCore\Helper\Registry $registry,
        $data = null
    ) {
        parent::__construct($objectManager, $request, $registry, $data);
        $this->productRespository = $productRespository;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    protected function loadSource()
    {
        if ($this->data instanceof \Magento\Catalog\Model\Product) {
            return $this->data;
        }
        return $this->productRespository
            ->get($this->data['id']);
    }

    /**
     * Load source model
     * 
     * @return \Owebia\ShippingCore\Model\Wrapper\Product
     */
    public function load()
    {
        $this->source = $this->productRespository
            ->get($this->entity_id);
        $this->cache->setData([]);
        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \Owebia\ShippingCore\Model\Wrapper\AbstractWrapper::loadData()
     */
    protected function loadData($key)
    {
        switch ($key) {
            case 'attribute_set':
                return $this->createWrapper([ 'id' => (int) $this->{'attribute_set_id'} ], 'ProductAttributeSet');
            case 'stock_item':
                return $this->createWrapper([ 'product_id' => (int) $this->{'entity_id'} ], 'ProductStockItem');
            case 'category_id':
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->getSource();
                return $product->getCategoryId();
            case 'category':
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->getSource();
                return $product->getCategory();
            case 'category_ids':
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->getSource();
                return $this->getSource()->getCategoryIds();
            case 'categories':
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->getSource();
                $categories = [];
                foreach ($product->getCategoryCollection() as $category) {
                    $categories[] = $category;
                }
                return $categories;
            default:
                return parent::loadData($key);
        }
    }
}
