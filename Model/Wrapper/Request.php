<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class Request extends SourceWrapper
{
    /**
     * @var RateRequest|null
     */
    private ?RateRequest $request;

    /**
     * @param WrapperContext $wrapperContext
     * @param RateRequest|null $request
     */
    public function __construct(
        WrapperContext $wrapperContext,
        RateRequest $request = null
    ) {
        $this->request = $request;
        parent::__construct($wrapperContext);
    }

    /**
     * @return RateRequest|null
     */
    public function getRequest(): ?RateRequest
    {
        return $this->request;
    }

    /**
     * @return QuoteModel|null
     */
    protected function loadSource(): ?object
    {
        return $this->getRequest();
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function loadData(string $key)
    {
        switch ($key) {
            case 'all_items':
                return parent::loadData($key) ?? [];
            case 'dest_postcode':
                return parent::loadData($key) ?? '';
        }

        return parent::loadData($key);
    }
}
