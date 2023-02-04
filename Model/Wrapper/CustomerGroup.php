<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\DataObject;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class CustomerGroup extends SourceWrapper
{
    /**
     * @var GroupRepositoryInterface
     */
    private GroupRepositoryInterface $groupRepository;

    /**
     * @param GroupRepositoryInterface $groupRepository
     * @param WrapperContext $wrapperContext
     * @param mixed $data
     */
    public function __construct(
        GroupRepositoryInterface $groupRepository,
        WrapperContext $wrapperContext,
        $data = null
    ) {
        $this->groupRepository = $groupRepository;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @return GroupInterface|null
     */
    protected function loadSource(): ?object
    {
        $quote = $this->wrapperContext->getQuote();
        $customerGroupId = $quote ? $quote->getCustomerGroupId() : null;
        return $customerGroupId ? $this->groupRepository->getById($customerGroupId) : null;
    }
}
