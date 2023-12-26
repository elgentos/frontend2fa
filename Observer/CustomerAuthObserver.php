<?php

/**
 * Copyright Youwe. All rights reserved.
 * https://www.youweagency.com
 */

declare(strict_types=1);

namespace Elgentos\Frontend2FA\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\ObserverInterface;

class CustomerAuthObserver implements ObserverInterface
{
    public function __construct(
        private readonly Session $customerSession
    ) {
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->customerSession->set2faSuccessful(false);
    }
}
