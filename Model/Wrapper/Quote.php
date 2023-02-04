<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

class Quote extends SourceWrapper
{
    /**
     * @var array
     */
    protected $additionalAttributes = [
        '__subtotal_excl_tax',
        '__subtotal_incl_tax',
        '__subtotal_with_discount_excl_tax',
        '__subtotal_with_discount_incl_tax',
    ];

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $taxConfig;

    /**
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \Magento\Framework\Escaper $escaper
     * @param \Owebia\SharedPhpConfig\Helper\Registry $registry
     * @param mixed $data
     */
    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\Escaper $escaper,
        \Owebia\SharedPhpConfig\Helper\Registry $registry,
        $data = null
    ) {
        parent::__construct(
            $objectManager,
            $backendAuthSession,
            $escaper,
            $registry,
            $data
        );
        $this->taxConfig = $taxConfig;
    }

    /**
     * @return \Magento\Quote\Model\Quote|null
     */
    protected function loadSource()
    {
        // Get quote from \Magento\Quote\Model\Quote\Address\RateRequest if possible
        $requestWrapper = $this->registry->get('request');
        if (isset($requestWrapper)
            && $requestWrapper->getSource() instanceof \Magento\Quote\Model\Quote\Address\RateRequest
        ) {
            $request = $requestWrapper->getSource();
            if ($items = $request->getAllItems()) {
                foreach ($items as $item) {
                    if ($quote = $item->getQuote()) {
                        return $quote;
                    }
                }
            }
        }

        if ($this->isBackendOrder()) { // For backend orders
            $session = $this->objectManager
                ->get(\Magento\Backend\Model\Session\Quote::class);
        } else {
            $session = $this->objectManager
                ->get(\Magento\Checkout\Model\Session::class);
        }

        return $session->getQuote();
    }

    protected function calculateSubtotals()
    {
        $subtotals = [
            '__subtotal_excl_tax',
            '__subtotal_incl_tax',
            '__subtotal_with_discount_excl_tax',
            '__subtotal_with_discount_incl_tax',
        ];

        $requestWrapper = $this->registry->get('request');
        $request = $requestWrapper->getSource();
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
            $parentItem = isset($items[$parentItemId]) ? $items[$parentItemId] : null;
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

    /**
     * {@inheritDoc}
     * @see \Owebia\SharedPhpConfig\Model\Wrapper\AbstractWrapper::loadData()
     */
    protected function loadData($key)
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
}
