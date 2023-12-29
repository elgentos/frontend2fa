<?php

declare(strict_types=1);

namespace Elgentos\Frontend2FA\Plugin;

use Elgentos\Frontend2FA\Api\ConfigInterface;
use Elgentos\Frontend2FA\Api\TfaCheckInterface;
use Magento\Customer\Controller\Account\LoginPost;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Psr\Log\LoggerInterface;

class LoginPostPlugin
{
    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly Context $context,
        private readonly ConfigInterface $config,
        private readonly TfaCheckInterface $tfaCheck,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param LoginPost $subject
     * @param Redirect $result
     * @return Redirect
     */
    public function afterExecute(LoginPost $subject, Redirect $result): Redirect
    {
        if (!$this->config->isEnabled()) {
            return $this;
        }

        if ($this->customerSession->get2faSuccessful()) {
            $this->logger->info('LoginPostPlugin get2faSuccessful true');
            return $result;
        }

        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->customerSession->getCustomer();
        if (!$customer->getId() || !$this->customerSession->isLoggedIn()) {
            $this->logger->info('LoginPostPlugin isLoggedIn false');
            return $result;
        }
        $this->logger->info('LoginPostPlugin Redirect');
        $resultRedirectFactory = $this->context->getResultRedirectFactory();
        $resultRedirect = $resultRedirectFactory->create();
        if ($this->tfaCheck->is2faConfiguredForCustomer($customer)) {
            return $resultRedirect->setPath(
                TfaCheckInterface::FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_PATH,
                $this->getRedirectReferer($subject)
            );
        } elseif ($this->tfaCheck->isCustomerInForced2faGroup($customer)) {
            return $resultRedirect->setPath(
                TfaCheckInterface::FRONTEND_2_FA_ACCOUNT_SETUP_PATH,
                $this->getRedirectReferer($subject)
            );
        }

        return $result;
    }

    private function getRedirectReferer($subject): array
    {
        if (empty($subject->getRequest()->getParam('referer'))) {
            return [];
        }

        return [
            'referer' => $subject->getRequest()->getParam('referer'),
        ];
    }
}
