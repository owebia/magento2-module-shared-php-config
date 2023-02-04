<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model;

use Magento\Backend\Model\Auth\Session as BackendAuthSession;
use Magento\Backend\Model\Session\Quote as BackendQuoteSession;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Model\Quote;
use Owebia\SharedPhpConfig\Model\Wrapper;

class WrapperContext
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var BackendAuthSession
     */
    private $backendAuthSession;

    /**
     * @param Escaper $escaper
     * @param ObjectManagerInterface $objectManager
     * @param BackendAuthSession $backendAuthSession
     */
    public function __construct(
        Escaper $escaper,
        ObjectManagerInterface $objectManager,
        BackendAuthSession $backendAuthSession
    ) {
        $this->escaper = $escaper;
        $this->objectManager = $objectManager;
        $this->backendAuthSession = $backendAuthSession;
    }

    /**
     * @return Escaper
     */
    public function getEscaper(): Escaper
    {
        return $this->escaper;
    }

    /**
     * Create new object instance
     *
     * @param string $type
     * @param array $arguments
     * @return mixed
     */
    public function create(string $type, array $arguments = [])
    {
        return $this->objectManager->create($type, $arguments);
    }

    /**
     * Retrieve cached object instance
     *
     * @param string $type
     * @return mixed
     */
    public function get(string $type)
    {
        return $this->objectManager->get($type);
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function wrap($data)
    {
        if (!isset($data)
            || is_bool($data)
            || is_int($data)
            || is_float($data)
            || is_string($data)
        ) {
            return $data;
        } elseif (is_array($data)) {
            return $data;
        } elseif (is_object($data)) {
            if ($data instanceof Wrapper\AbstractWrapper) {
                return $data;
            } elseif ($data instanceof \Closure) {
                return $data;
            } elseif ($data instanceof \Magento\Framework\Phrase) {
                return $data->__toString();
            } elseif ($data instanceof \Magento\Quote\Model\Quote\Item) {
                return $this->createWrapper(Wrapper\QuoteItem::class, ['data' => $data]);
            } elseif ($data instanceof ProductInterface) {
                return $this->createWrapper(Wrapper\Product::class, ['data' => $data]);
            } elseif ($data instanceof CategoryInterface) {
                return $this->createWrapper(Wrapper\Category::class, ['data' => $data]);
            } else {
                return $this->createWrapper(Wrapper\SourceWrapper::class, ['data' => $data]);
            }
        } else {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            throw new LocalizedException(__("Unsupported type %1", gettype($data)));
        }
    }

    /**
     * @param string $type
     * @param array $arguments
     * @return Wrapper\AbstractWrapper
     */
    public function createWrapper(string $type, array $arguments = []): Wrapper\AbstractWrapper
    {
        $arguments['wrapperContext'] = $this;
        return $this->create($type, $arguments);
    }

    /**
     * @return Quote
     */
    public function getQuote(): Quote
    {
        if ($this->backendAuthSession->isLoggedIn()) { // For backend orders
            /** @var BackendQuoteSession $session */
            $session = $this->get(BackendQuoteSession::class);
            return $session->getQuote();
        } else {
            /** @var CheckoutSession $session */
            $session = $this->get(CheckoutSession::class);
            return $session->getQuote();
        }
    }

    /**
     * @return BackendAuthSession
     */
    public function getBackendAuthSession(): BackendAuthSession
    {
        return $this->backendAuthSession;
    }
}
