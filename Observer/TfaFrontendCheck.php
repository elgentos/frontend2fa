<?php

namespace Elgentos\Frontend2FA\Observer;

use Elgentos\Frontend2FA\Api\ConfigInterface;
use Elgentos\Frontend2FA\Model\SecretFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class TfaFrontendCheck implements ObserverInterface
{
    const FRONTEND_2_FA_ACCOUNT_SETUP_ROUTE = 'elgentos_frontend2fa_frontend_route_account_setup';
    const FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_ROUTE = 'elgentos_frontend2fa_frontend_route_account_authenticate';
    const CUSTOMER_ACCOUNT_LOGOUT_ROUTE = 'customer_account_logout';
    const CUSTOMER_LOAD_SECTION = 'customer_section_load';

    const FRONTEND_2_FA_ACCOUNT_SETUP_PATH = 'frontend2fa/account/setup';
    const FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_PATH = 'frontend2fa/account/authenticate';


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
        if (in_array($this->request->getFullActionName(), $this->getAllowedRoutes($customer))) {
            $this->logger->info('TfaFrontendCheck getAllowedRoutes true');
            return $this;
        }

        if ($this->is2faConfiguredForCustomer($customer)) {
            $this->logger->info('TfaFrontendCheck is2faConfiguredForCustomer true');
            // Redirect to 2FA authentication page
            $redirectionUrl = $this->url->getUrl(self::FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_PATH);
            $this->logger->info('TfaFrontendCheck is2faConfiguredForCustomer redirect',[$redirectionUrl]);
            $this->redirect->setRedirect($redirectionUrl);
        } elseif ($this->isCustomerInForced2faGroup($customer)) {
            // Redirect to 2FA setup page
            $this->messageManager->addNoticeMessage(__('You need to set up Two Factor Authentication before continuing.'));
            $redirectionUrl = $this->url->getUrl(self::FRONTEND_2_FA_ACCOUNT_SETUP_PATH);
            $this->redirect->setRedirect($redirectionUrl);
        }

        $currentPage = $this->url->getUrl('*/*/*');
        if ($currentPage && str_contains($currentPage, 'checkout')) {
            $this->logger->info('TfaFrontendCheck checkout redirect',[$currentPage]);
            $this->customerSession->setBefore2faUrl($currentPage);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getForced2faCustomerGroups()
    {
        $forced2faCustomerGroups = $this->config->getValue(self::ELGENTOS_AUTHENTICATOR_GENERAL_FORCED_GROUPS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE) ?? '';

        return array_filter(array_map('trim', explode(',', $forced2faCustomerGroups)));
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return bool
     */
    public function isCustomerInForced2faGroup(\Magento\Customer\Model\Customer $customer)
    {
        return in_array($customer->getGroupId(), $this->config->getForced2faCustomerGroups());
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return bool
     */
    public function is2faConfiguredForCustomer(\Magento\Customer\Model\Customer $customer)
    {
        $secret = $this->secretFactory->create()->load($customer->getId(), 'customer_id');
        if ($secret->getId() && $secret->getSecret()) {
            return true;
        }

        return false;
    }

    public function getAllowedRoutes(\Magento\Customer\Model\Customer $customer)
    {
        // When 2FA is configured, the customer needs to authenticate
        if ($this->is2faConfiguredForCustomer($customer)) {
            $routes = [self::FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_ROUTE];
        } else {
            $routes = [self::FRONTEND_2_FA_ACCOUNT_SETUP_ROUTE];
        }

        // Customer should always be able to log out
        $routes[] = self::CUSTOMER_ACCOUNT_LOGOUT_ROUTE;
        $routes[] = self::CUSTOMER_LOAD_SECTION;

        return $routes;
    }
}
