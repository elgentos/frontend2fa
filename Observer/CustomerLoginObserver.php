<?php

/**
 * Copyright Youwe. All rights reserved.
 * https://www.youweagency.com
 */

declare(strict_types=1);

namespace Elgentos\Frontend2FA\Observer;

use Elgentos\Frontend2FA\Api\ConfigInterface;
use Elgentos\Frontend2FA\Api\TfaCheckInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ResponseFactory;
use Psr\Log\LoggerInterface;

class CustomerLoginObserver implements ObserverInterface
{

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly Session $customerSession,
        private readonly UrlInterface $url,
        private readonly ResponseFactory $responseFactory,
        private readonly TfaCheckInterface $tfaCheck,
        private readonly LoggerInterface $logger
    ) {
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if (!$this->config->isEnabled()) {
            return;
        }
        if ($this->customerSession->get2faSuccessful()) {
            return;
        }

        $currentPage = $this->url->getUrl('*/*/*');
        if ($currentPage && str_contains($currentPage, 'checkout')) {
            $this->customerSession->setBefore2faUrl($currentPage);
        }

        $customer = $this->customerSession->getCustomer();
        if ($this->tfaCheck->is2faConfiguredForCustomer($customer)) {
            $redirectionUrl = $this->url->getUrl(TfaCheckInterface::FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_PATH);
            $this->responseFactory
                ->create()
                ->setRedirect($redirectionUrl)->sendResponse();
        } elseif ($this->tfaCheck->isCustomerInForced2faGroup($customer)) {
            // Redirect to 2FA setup page
            $this->messageManager->addNoticeMessage(__('You need to set up Two Factor Authentication before continuing.'));
            $redirectionUrl = $this->url->getUrl(TfaCheckInterface::FRONTEND_2_FA_ACCOUNT_SETUP_PATH);
            $this->responseFactory
                ->create()
                ->setRedirect($redirectionUrl)->sendResponse();
        }

        return $this;
    }
}
