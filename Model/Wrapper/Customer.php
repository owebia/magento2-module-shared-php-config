<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Customer\Api\Data\CustomerInterface;
use Owebia\SharedPhpConfig\Model\WrapperContext;
use Owebia\SharedPhpConfig\Model\Wrapper\Request as RequestWrapper;

class Customer extends SourceWrapper
{
    /**
     * @var RequestWrapper|null
     */
    private ?RequestWrapper $requestWrapper;

    /**
     * @param WrapperContext $wrapperContext
     * @param RequestWrapper|null $requestWrapper
     * @param mixed $data
     */
    public function __construct(
        WrapperContext $wrapperContext,
        ?RequestWrapper $requestWrapper = null,
        $data = null
    ) {
        $this->requestWrapper = $requestWrapper;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @return CustomerInterface|null
     */
    protected function loadSource(): ?object
    {
        $quote = $this->requestWrapper ? $this->requestWrapper->getQuote() : $this->wrapperContext->getQuote();
        if ($quote && ($customer = $quote->getCustomer())) {
            return $customer;
        }

        return null;
    }
}
