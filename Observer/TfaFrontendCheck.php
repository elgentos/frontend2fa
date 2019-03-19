<?php

namespace Elgentos\Frontend2FA\Observer;

use Elgentos\Frontend2FA\Model\SecretFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;

class TfaFrontendCheck implements ObserverInterface {

    const ELGENTOS_AUTHENTICATOR_GENERAL_ENABLE = 'elgentos_authenticator/general/enable';
    const ELGENTOS_AUTHENTICATOR_GENERAL_FORCED_GROUPS = 'elgentos_authenticator/general/forced_groups';

    const FRONTEND_2_FA_ACCOUNT_SETUP_ROUTE = 'elgentos_frontend2fa_frontend_route_account_setup';
    const FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_ROUTE = 'elgentos_frontend2fa_frontend_route_account_authenticate';
    const CUSTOMER_ACCOUNT_LOGOUT_ROUTE = 'customer_account_logout';

    const FRONTEND_2_FA_ACCOUNT_SETUP_PATH = 'frontend2fa/account/setup';
    const FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_PATH = 'frontend2fa/account/authenticate';

    /**
     * @var ScopeConfigInterface
     */
    public $config;
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
     * @param ScopeConfigInterface $config
     * @param Http $redirect
     * @param SecretFactory $secretFactory
     * @param Session $customerSession
     * @param UrlInterface $url
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        ScopeConfigInterface $config,
        Http $redirect,
        SecretFactory $secretFactory,
        Session $customerSession,
        UrlInterface $url,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->config = $config;
        $this->url = $url;
        $this->redirect = $redirect;
        $this->secretFactory = $secretFactory;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->getValue(self::ELGENTOS_AUTHENTICATOR_GENERAL_ENABLE)) {
            return $this;
        }

        if ($this->customerSession->get2faSuccessful()) {
            return $this;
        }

        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->customerSession->getCustomer();
        if (!$customer->getId() || !$this->customerSession->isLoggedIn())  {
            return $this;
        }

        if (in_array($observer->getEvent()->getRequest()->getFullActionName(), $this->getAllowedRoutes($customer))) {
            return $this;
        }

        if ($this->is2faConfiguredForCustomer($customer)) {
            // Redirect to 2FA authentication page
            $redirectionUrl = $this->url->getUrl(self::FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_PATH);
            $this->redirect->setRedirect($redirectionUrl);
        } elseif ($this->isCustomerInForced2faGroup($customer)) {
            // Redirect to 2FA setup page
            $this->messageManager->addNoticeMessage(__('You need to set up Two Factor Authentication before continuing.'));
            $redirectionUrl = $this->url->getUrl(self::FRONTEND_2_FA_ACCOUNT_SETUP_PATH);
            $this->redirect->setRedirect($redirectionUrl);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getForced2faCustomerGroups()
    {
        $forced2faCustomerGroups = $this->config->getValue(self::ELGENTOS_AUTHENTICATOR_GENERAL_FORCED_GROUPS);
        return array_filter(array_map('trim', explode(',', $forced2faCustomerGroups)));
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return bool
     */
    public function isCustomerInForced2faGroup(\Magento\Customer\Model\Customer $customer)
    {
        return in_array($customer->getGroupId(), $this->getForced2faCustomerGroups());
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
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

        return $routes;
    }

}