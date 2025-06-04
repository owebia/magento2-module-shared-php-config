<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Owebia\SharedPhpConfig\Model\WrapperContext;
use Owebia\SharedPhpConfig\Model\Wrapper\Request as RequestWrapper;

class CustomerGroup extends SourceWrapper
{
    /**
     * @var GroupRepositoryInterface
     */
    private GroupRepositoryInterface $groupRepository;

    /**
     * @var RequestWrapper|null
     */
    private ?RequestWrapper $requestWrapper;

    /**
     * @param GroupRepositoryInterface $groupRepository
     * @param WrapperContext $wrapperContext
     * @param RequestWrapper|null $requestWrapper
     * @param mixed $data
     */
    public function __construct(
        GroupRepositoryInterface $groupRepository,
        WrapperContext $wrapperContext,
        ?RequestWrapper $requestWrapper = null,
        $data = null
    ) {
        $this->groupRepository = $groupRepository;
        $this->requestWrapper = $requestWrapper;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @return GroupInterface|null
     */
    protected function loadSource(): ?object
    {
        $quote = $this->requestWrapper ? $this->requestWrapper->getQuote() : $this->wrapperContext->getQuote();
        $customerGroupId = $quote ? $quote->getCustomerGroupId() : 0;
        return $this->groupRepository->getById($customerGroupId); // 0 is the customer group ID for "NOT LOGGED IN"
    }
}
