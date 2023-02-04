<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

class Store extends SourceWrapper
{
    /**
     * @var array
     */
    protected $aliasMap = [
        'id' => 'store_id'
    ];

    /**
     * @var array
     */
    protected $additionalAttributes = [ 'name', 'address', 'phone' ];

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \Magento\Framework\Escaper $escaper
     * @param \Owebia\SharedPhpConfig\Helper\Registry $registry
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     * @param mixed $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\Escaper $escaper,
        \Owebia\SharedPhpConfig\Helper\Registry $registry,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        $data = null
    ) {
        parent::__construct($objectManager, $backendAuthSession, $escaper, $registry, $data);
        $this->storeRepository = $storeRepository;
    }

    /**
     * @return \Magento\Framework\DataObject|null
     */
    protected function loadSource()
    {
        return $this->storeRepository
            ->getById($this->getStoreId());
    }

    /**
     * {@inheritDoc}
     * @see \Owebia\SharedPhpConfig\Model\Wrapper\AbstractWrapper::loadData()
     */
    protected function loadData($key)
    {
        switch ($key) {
            case 'name':
            case 'address':
            case 'phone':
                return $this->getSource()
                    ->getConfig('general/store_information/' . $key);
        }
        return parent::loadData($key);
    }
}
