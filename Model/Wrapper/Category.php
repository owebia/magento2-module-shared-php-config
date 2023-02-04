<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class Category extends SourceWrapper
{
    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param WrapperContext $wrapperContext
     * @param mixed $data
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        WrapperContext $wrapperContext,
        $data = null
    ) {
        $this->categoryRepository = $categoryRepository;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @return CategoryInterface|null
     */
    protected function loadSource(): ?object
    {
        return $this->data instanceof CategoryInterface
            ? $this->data
            : $this->categoryRepository->get($this->data['id']);
    }

    /**
     * Load source model
     *
     * @return $this
     */
    public function load()
    {
        $this->source = $this->categoryRepository->get($this->entity_id);
        $this->cache->setData([]);
        return $this;
    }
}
