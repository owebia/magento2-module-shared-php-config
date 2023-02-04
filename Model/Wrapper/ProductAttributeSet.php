<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class ProductAttributeSet extends SourceWrapper
{
    /**
     * @var AttributeSetRepositoryInterface
     */
    private AttributeSetRepositoryInterface $attributeSetRepository;

    /**
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param WrapperContext $wrapperContext
     * @param mixed $data
     */
    public function __construct(
        AttributeSetRepositoryInterface $attributeSetRepository,
        WrapperContext $wrapperContext,
        $data = null
    ) {
        $this->attributeSetRepository = $attributeSetRepository;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @return AttributeSetInterface|null
     */
    protected function loadSource(): ?object
    {
        return $this->attributeSetRepository->get($this->data['id']);
    }
}
