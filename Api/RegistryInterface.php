<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Api;

interface RegistryInterface
{
    /**
     * @param string $name
     * @param int|null $scopeIndex
     * @return mixed
     */
    public function get(string $name, ?int $scopeIndex = null);

    /**
     * @param string $name
     * @return mixed
     */
    public function getGlobal(string $name);

    /**
     * @param string $name
     * @param mixed $value
     * @param bool $override
     * @param int $scopeIndex
     */
    public function register(string $name, $value, bool $override = false, ?int $scopeIndex = null): void;

    /**
     * @param string $name
     */
    public function declareGlobalAtCurrentScope(string $name): void;

    /**
     * @return int
     */
    public function getCurrentScopeIndex(): int;

    /**
     * Create Scope
     */
    public function createScope(): void;

    /**
     * Delete Scope
     */
    public function deleteScope(): void;
}
