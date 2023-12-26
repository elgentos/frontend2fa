<?php

namespace Elgentos\Frontend2FA\Observer;

use Elgentos\Frontend2FA\Api\ConfigInterface;
use Elgentos\Frontend2FA\Api\TfaCheckInterface;
use Elgentos\Frontend2FA\Model\SecretFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\FunctionalTestingFramework\DataTransport\Auth\Tfa;
use Psr\Log\LoggerInterface;

class TfaFrontendCheck implements ObserverInterface
{
    /**
     * @var UrlInterface
     */
    public $url;
    /**
     * @var RedirectInterface
     */
    public $redirect;
    /**
     * @var SecretFactory
     */
    public $secretFactory;
    /**
     * @var Session
     */
    public $customerSession;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;

    /**
     * TfaFrontendCheck constructor.
     *
     * @param ScopeConfigInterface                        $config
     * @param Http                                        $redirect
     * @param SecretFactory                               $secretFactory
     * @param Session                                     $customerSession
     * @param UrlInterface                                $url
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        private readonly ConfigInterface $config,
        Http $redirect,
        SecretFactory $secretFactory,
        Session $customerSession,
        UrlInterface $url,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        private readonly RequestInterface $request,
        private readonly TfaCheckInterface $tfaCheck,
        private readonly LoggerInterface $logger
    ) {
        $this->url = $url;
        $this->redirect = $redirect;
        $this->secretFactory = $secretFactory;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return $this;
        }

        if ($this->customerSession->get2faSuccessful()) {
            $this->logger->info('TfaFrontendCheck get2faSuccessful true');
            return $this;
        }

        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->customerSession->getCustomer();
        if (!$customer->getId() || !$this->customerSession->isLoggedIn()) {
            $this->logger->info('TfaFrontendCheck isLoggedIn false');
            return $this;
        }
        $this->logger->info('TfaFrontendCheck isLoggedIn true');
        if (in_array($observer->getEvent()->getRequest()->getFullActionName(),
            $this->tfaCheck->getAllowedRoutes($customer))
        ) {
            $this->logger->info('TfaFrontendCheck getAllowedRoutes true');
            return $this;
        }
        $currentPage = $this->url->getUrl('*/*/*');
        if ($currentPage && str_contains($currentPage, 'checkout')) {
            $this->logger->info('TfaFrontendCheck checkout redirect',[$currentPage]);
            $this->customerSession->setBefore2faUrl($currentPage);
        }
        if ($this->tfaCheck->is2faConfiguredForCustomer($customer)) {
            $this->logger->info('TfaFrontendCheck is2faConfiguredForCustomer true');
            // Redirect to 2FA authentication page
            $redirectionUrl = $this->url->getUrl(TfaCheckInterface::FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_PATH);
            $this->logger->info('TfaFrontendCheck is2faConfiguredForCustomer redirect',[$redirectionUrl]);
            $observer->getControllerAction()->getResponse()->setRedirect($redirectionUrl);
        } elseif ($this->tfaCheck->isCustomerInForced2faGroup($customer)) {
            // Redirect to 2FA setup page
            $this->messageManager->addNoticeMessage(__('You need to set up Two Factor Authentication before continuing.'));
            $redirectionUrl = $this->url->getUrl(TfaCheckInterface::FRONTEND_2_FA_ACCOUNT_SETUP_PATH);
            $observer->getControllerAction()->getResponse()->setRedirect($redirectionUrl);
        }

        return $this;
    }
}
