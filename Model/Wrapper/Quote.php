<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Tax\Model\Config as TaxConfig;
use Owebia\SharedPhpConfig\Model\Wrapper\Request as RequestWrapper;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class Quote extends SourceWrapper
{
    /**
     * @var string[]
     */
    protected array $additionalAttributes = [
        '__subtotal_excl_tax',
        '__subtotal_incl_tax',
        '__subtotal_with_discount_excl_tax',
        '__subtotal_with_discount_incl_tax',
    ];

    /**
     * @var TaxConfig
     */
    private TaxConfig $taxConfig;

    /**
     * @var RequestWrapper|null
     */
    private ?RequestWrapper $requestWrapper;

    /**
     * @param TaxConfig $taxConfig
     * @param WrapperContext $wrapperContext
     * @param RequestWrapper|null $requestWrapper
     * @param mixed $data
     */
    public function __construct(
        TaxConfig $taxConfig,
        WrapperContext $wrapperContext,
        RequestWrapper $requestWrapper = null,
        $data = null
    ) {
        $this->taxConfig = $taxConfig;
        $this->requestWrapper = $requestWrapper;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @return QuoteModel|null
     */
    protected function getQuote(): ?QuoteModel
    {
        return $this->getSource();
    }

    /**
     * @return QuoteModel|null
     */
    protected function loadSource(): ?object
    {
        // Get quote from RateRequest if possible
        if ($this->requestWrapper && $this->requestWrapper->getSource() instanceof RateRequest) {
            /** @var RateRequest $request */
            $request = $this->requestWrapper->getSource();
            if ($items = $request->getAllItems()) {
                foreach ($items as $item) {
                    if ($quote = $item->getQuote()) {
                        return $quote;
                    }
                }
            }
        }

        return $this->wrapperContext->getQuote();
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function loadData(string $key)
    {
        switch ($key) {
            case '__subtotal_excl_tax':
            case '__subtotal_incl_tax':
            case '__subtotal_with_discount_excl_tax':
            case '__subtotal_with_discount_incl_tax':
                if (!isset($this->cache['__subtotal_excl_tax'])) {
                    $this->calculateSubtotals();
                }
                return $this->cache[$key] ?? null;
        }

        return parent::loadData($key);
    }

    /**
     * Calculate subtotals
     */
    private function calculateSubtotals(): void
    {
        if (!$this->requestWrapper) {
            return;
        }

        $request = $this->requestWrapper->getSource();
        $items = $request->getAllItems();
        if (!is_array($items)) {
            return;
        }

        // Discount includes tax only when the option "Including Tax" for "Catalog Prices" is selected
        $discountIncludesTax = $this->taxConfig->priceIncludesTax($request->getStoreId());

        // Do not use quote to retrieve values, totals are not available
        $subtotalExclTax = 0;
        $subtotalInclTax = 0;
        $subtotalWithDiscountExclTax = 0;
        $subtotalWithDiscountInclTax = 0;

        foreach ($items as $item) {
            /*$type = $item->getProduct()->getTypeId();
            $parentItemId = $item->getParentItemId();
            $parentItem = $items[$parentItemId] ?? null;
            $parentType = isset($parentItem) ? $parentItem->getProduct()->getTypeId() : null;*/

            $baseRowTotalExclTax = $item->getBaseRowTotal();
            $baseDiscountAmount = $item->getBaseDiscountAmount();
            $taxFactor = 1 + $item->getTaxPercent() / 100;
            $discountExclTax = $discountIncludesTax ? $baseDiscountAmount / $taxFactor : $baseDiscountAmount;
            $discountInclTax = $discountIncludesTax ? $baseDiscountAmount : $baseDiscountAmount * $taxFactor;

            $subtotalExclTax += $baseRowTotalExclTax;
            $subtotalWithDiscountExclTax += $baseRowTotalExclTax - $discountExclTax;
            $subtotalWithDiscountInclTax += $item->getBaseRowTotalInclTax() - $discountInclTax;
            $subtotalInclTax += $item->getBaseRowTotalInclTax();
        }

        $this->cache['__subtotal_excl_tax'] = $subtotalExclTax;
        $this->cache['__subtotal_incl_tax'] = $subtotalInclTax;
        $this->cache['__subtotal_with_discount_excl_tax'] = $subtotalWithDiscountExclTax;
        $this->cache['__subtotal_with_discount_incl_tax'] = $subtotalWithDiscountInclTax;
    }
}
