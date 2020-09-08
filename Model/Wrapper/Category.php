<?php
/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Owebia\SharedPhpConfig\Model\Wrapper;

class Category extends SourceWrapper
{

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRespository;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \Magento\Framework\Escaper $escaper
     * @param \Owebia\SharedPhpConfig\Helper\Registry $registry
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRespository
     * @param mixed $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\Escaper $escaper,
        \Owebia\SharedPhpConfig\Helper\Registry $registry,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRespository,
        $data = null
    ) {
        parent::__construct($objectManager, $backendAuthSession, $escaper, $registry, $data);
        $this->categoryRespository = $categoryRespository;
    }

    /**
     * @return \Magento\Framework\DataObject|null
     */
    protected function loadSource()
    {
        if ($this->data instanceof \Magento\Catalog\Api\Data\CategoryInterface) {

            return $this->categoryRespository
                        ->get($this->data['entity_id']);
        }
    }

    /**
     * Load source model
     *
     * @return \Owebia\SharedPhpConfig\Model\Wrapper\Category
     */
    public function load()
    {
        $this->source = $this->categoryRespository
            ->get($this->entity_id);
        $this->cache->setData([]);
        return $this;
    }
}
