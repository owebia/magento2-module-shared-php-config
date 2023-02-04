<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Owebia\SharedPhpConfig\Model\Wrapper\Request as RequestWrapper;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class Store extends SourceWrapper
{
    /**
     * @var string[]
     */
    protected array $aliasMap = [
        'id' => 'store_id'
    ];

    /**
     * @var string[]
     */
    protected array $additionalAttributes = ['name', 'address', 'phone'];

    /**
     * @var StoreRepositoryInterface
     */
    private StoreRepositoryInterface $storeRepository;

    /**
     * @var RequestWrapper|null
     */
    private ?RequestWrapper $requestWrapper;

    /**
     * @param StoreRepositoryInterface $storeRepository
     * @param WrapperContext $wrapperContext
     * @param RequestWrapper|null $requestWrapper
     * @param mixed $data
     */
    public function __construct(
        StoreRepositoryInterface $storeRepository,
        WrapperContext $wrapperContext,
        RequestWrapper $requestWrapper = null,
        $data = null
    ) {
        $this->storeRepository = $storeRepository;
        $this->requestWrapper = $requestWrapper;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @return StoreInterface|null
     */
    protected function loadSource(): ?object
    {
        $storeId = $this->requestWrapper
            && ($request = $this->requestWrapper->getRequest())
            ? $request->getStoreId()
            : null;
        return $this->storeRepository->getById($storeId);
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function loadData(string $key)
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
