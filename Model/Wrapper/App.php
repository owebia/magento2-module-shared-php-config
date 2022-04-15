<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Owebia\SharedPhpConfig\Model\Wrapper;

class App extends SourceWrapper
{
    /**
     * @var array
     */
    protected $additionalAttributes = [
        'area_code',
    ];

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \Magento\Framework\Escaper $escaper
     * @param \Owebia\SharedPhpConfig\Helper\Registry $registry
     * @param \Magento\Framework\App\State $appState
     * @param mixed $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\Escaper $escaper,
        \Owebia\SharedPhpConfig\Helper\Registry $registry,
        \Magento\Framework\App\State $appState,
        $data = []
    ) {
        parent::__construct($objectManager, $backendAuthSession, $escaper, $registry, $data);
        $this->appState = $appState;
    }

    /**
     * @return \Magento\Framework\DataObject|null
     */
    protected function loadSource()
    {
        return $this->appState;
    }

    /**
     * {@inheritDoc}
     * @see Wrapper\AbstractWrapper::loadData()
     */
    protected function loadData($key)
    {
        switch ($key) {
            case 'area_code':
                return $this->getAreaCode();
            default:
                return parent::loadData($key);
        }
    }

    public function getAreaCode()
    {
        return $this->appState->getAreaCode();
    }

    public function isAdminArea()
    {
        return $this->getAreaCode() === \Magento\Framework\App\Area::AREA_ADMINHTML;
    }

    public function isFrontendArea()
    {
        return $this->getAreaCode() === \Magento\Framework\App\Area::AREA_WEBAPI_REST;
    }
}
