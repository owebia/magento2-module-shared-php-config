<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model;

use Owebia\SharedPhpConfig\Api\RegistryInterface;
use Owebia\SharedPhpConfig\Model\Wrapper;

class Registry implements RegistryInterface
{
    /**
     * @var array
     */
    private array $data = [
        [] // Main Scope
    ];

    /**
     * @var array
     */
    private array $globalVariables = [
        [] // Main Scope
    ];

    /**
     * @var WrapperContext
     */
    private WrapperContext $wrapperContext;

    /**
     * @param WrapperContext $wrapperContext
     */
    public function __construct(
        WrapperContext $wrapperContext
    ) {
        $this->wrapperContext = $wrapperContext;
    }

    /**
     * @param \Magento\Framework\DataObject $request
     */
    public function init(\Magento\Framework\DataObject $request): void
    {
        $this->data = [
            []
        ];
        $this->register(
            'app',
            $this->wrapperContext->createWrapper(Wrapper\App::class)
        );
        $requestWrapper = $this->wrapperContext->createWrapper(Wrapper\Request::class, ['request' => $request]);
        $this->register('request', $requestWrapper);
        $this->register(
            'quote',
            $this->wrapperContext->createWrapper(Wrapper\Quote::class, [
                'requestWrapper' => $requestWrapper,
            ])
        );
        $this->register(
            'customer',
            $this->wrapperContext->createWrapper(Wrapper\Customer::class)
        );
        $this->register(
            'customer_group',
            $this->wrapperContext->createWrapper(Wrapper\CustomerGroup::class)
        );
        $this->register(
            'variable',
            $this->wrapperContext->createWrapper(Wrapper\Variable::class, [
                'requestWrapper' => $requestWrapper,
            ])
        );
        $this->register(
            'store',
            $this->wrapperContext->createWrapper(Wrapper\Store::class, [
                'requestWrapper' => $requestWrapper,
            ])
        );
    }

    /**
     * @param string $name
     * @param int|null $scopeIndex
     * @return mixed
     */
    public function get(string $name, ?int $scopeIndex = null)
    {
        if (!isset($scopeIndex)) {
            $scopeIndex = $this->getCurrentScopeIndex();
        }

        if (isset($this->globalVariables[$scopeIndex][$name])) {
            $scopeIndex = 0;
        }

        return $this->data[$scopeIndex][$name] ?? null;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getGlobal(string $name)
    {
        return $this->get($name, 0);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param bool $override
     * @param int $scopeIndex
     */
    public function register(string $name, $value, bool $override = false, ?int $scopeIndex = null): void
    {
        if (!isset($scopeIndex)) {
            $scopeIndex = $this->getCurrentScopeIndex();
        }

        if (isset($this->globalVariables[$scopeIndex][$name])) {
            $scopeIndex = 0;
        }

        if (!$override && isset($this->data[$scopeIndex][$name])) {
            return;
        }

        $this->data[$scopeIndex][$name] = $value;
    }

    /**
     * @param string $name
     */
    public function declareGlobalAtCurrentScope(string $name): void
    {
        $scopeIndex = $this->getCurrentScopeIndex();
        if (!isset($this->globalVariables[$scopeIndex][$name])) {
            $this->globalVariables[$scopeIndex][$name] = true;
        }
    }

    /**
     * @return int
     */
    public function getCurrentScopeIndex(): int
    {
        return count($this->data) - 1;
    }

    /**
     * Create Scope
     */
    public function createScope(): void
    {
        $scopeIndex = $this->getCurrentScopeIndex() + 1;
        $this->data[$scopeIndex] = [];
        $this->globalVariables[$scopeIndex] = [];
    }

    /**
     * Delete Scope
     */
    public function deleteScope(): void
    {
        $scopeIndex = $this->getCurrentScopeIndex();
        unset($this->data[$scopeIndex]);
        unset($this->globalVariables[$scopeIndex]);
    }
}
