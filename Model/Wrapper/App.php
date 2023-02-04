<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Owebia\SharedPhpConfig\Model\Wrapper;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Owebia\SharedPhpConfig\Model\WrapperContext;

class App extends SourceWrapper
{
    /**
     * @var array
     */
    protected array $additionalAttributes = [
        'area_code',
    ];

    /**
     * @var State
     */
    private State $appState;

    /**
     * @param State $appState
     * @param WrapperContext $wrapperContext
     * @param mixed $data
     */
    public function __construct(
        State $appState,
        WrapperContext $wrapperContext,
        $data = []
    ) {
        $this->appState = $appState;
        parent::__construct($wrapperContext, $data);
    }

    /**
     * @return State|null
     */
    protected function loadSource(): ?object
    {
        return $this->appState;
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function loadData(string $key)
    {
        switch ($key) {
            case 'area_code':
                return $this->getAreaCode();
            default:
                return parent::loadData($key);
        }
    }

    /**
     * @return string
     */
    public function getAreaCode(): string
    {
        return $this->appState->getAreaCode();
    }

    /**
     * @return bool
     */
    public function isAdminArea(): bool
    {
        return $this->getAreaCode() === Area::AREA_ADMINHTML;
    }

    /**
     * @return bool
     */
    public function isFrontendArea(): bool
    {
        return $this->getAreaCode() === Area::AREA_WEBAPI_REST;
    }
}
