<?php

/**
 * Copyright © Owebia. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Owebia\SharedPhpConfig\Controller\Adminhtml\Debug;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;

class Clear extends \Magento\Backend\App\Action
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var Filesystem $filesystem */
        $filesystem = $this->_objectManager->get(Filesystem::class);
        $writeInterface = $filesystem->getDirectoryWrite(DirectoryList::LOG);

        $writeInterface->writeFile('owebia_advancedsettingcore.log', '');

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setUrl($this->getUrl('owebia_advancedsettingcore/debug/index'));
    }
}
