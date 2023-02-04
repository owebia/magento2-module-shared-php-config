<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class Customer extends SourceWrapper
{
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param WrapperContext $wrapperContext
     * @param mixed $data
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        WrapperContext $wrapperContext,
        $data = null
    ) {
        $this->customerRepository = $customerRepository;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @return CustomerInterface|null
     */
    protected function loadSource(): ?object
    {
        $quote = $this->wrapperContext->getQuote();
        if ($quote && ($customer = $quote->getCustomer())) {
            return $customer;
        }

        return null;
    }
}
