<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Variable\Model\VariableFactory;
use Owebia\SharedPhpConfig\Model\Wrapper\Request as RequestWrapper;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class Variable extends SourceWrapper
{
    /**
     * @var VariableFactory
     */
    private VariableFactory $variableFactory;

    /**
     * @var RequestWrapper|null
     */
    private ?RequestWrapper $requestWrapper;

    /**
     * @param VariableFactory $variableFactory
     * @param WrapperContext $wrapperContext
     * @param RequestWrapper|null $requestWrapper
     * @param mixed $data
     */
    public function __construct(
        VariableFactory $variableFactory,
        WrapperContext $wrapperContext,
        ?RequestWrapper $requestWrapper = null,
        $data = null
    ) {
        $this->variableFactory = $variableFactory;
        $this->requestWrapper = $requestWrapper;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @return \Magento\Variable\Model\Variable|null
     */
    protected function loadSource(): ?object
    {
        /** @var \Magento\Variable\Model\Variable $source */
        $source = $this->variableFactory->create();
        if (isset($this->data['code'])) {
            $storeId = $this->requestWrapper
                && ($request = $this->requestWrapper->getRequest())
                ? $request->getStoreId()
                : null;
            $source->setStoreId($storeId)
                ->loadByCode($this->data['code']);
        }

        return $source;
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function loadData(string $key)
    {
        if (isset($this->data['code'])) {
            return parent::loadData($key);
        }

        return $this->wrapperContext->create(
            static::class,
            ['data' => ['code' => $key]]
        );
    }

    /**
     * {@inheritDoc}
     * @see AbstractWrapper::getAdditionalData()
     */
    protected function getAdditionalData(): array
    {
        $data = parent::getAdditionalData();
        if (!isset($this->data['code'])) {
            foreach ($this->getSource()->getCollection() as $variable) {
                $data[$variable->getCode()] = $variable;
            }
        }

        return $data;
    }
}
