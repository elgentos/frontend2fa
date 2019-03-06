<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 5-3-19
 * Time: 13:36
 */

namespace Elgentos\Frontend2FA\Block;

use Elgentos\Frontend2FA\Observer\TfaFrontendCheck;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Neyamtux\Authenticator\Lib\PHPGangsta\GoogleAuthenticator;

class Authenticator extends \Neyamtux\Authenticator\Block\Authenticator
{
    /**
     * @var TfaFrontendCheck
     */
    public $observer;
    /**
     * @var Session
     */
    public $customerSession;
    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * Authenticator constructor.
     * @param Context $context
     * @param GoogleAuthenticator $googleAuthenticator
     * @param CatalogSession $session
     * @param TfaFrontendCheck $observer
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        GoogleAuthenticator $googleAuthenticator,
        CatalogSession $session,
        TfaFrontendCheck $observer,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $googleAuthenticator, $session, $data);
        $this->observer = $observer;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQRCodeUrl()
    {
        return $this->_googleAuthenticator->getQRCodeGoogleUrl($this->storeManager->getWebsite()->getName() . ' 2FA Login', $this->_googleSecret);
    }

    /**
     * Returns action url for authentication form
     *
     * @return string
     */
    public function getSetupFormAction()
    {
        return $this->getUrl('frontend2fa/account/setup', ['_secure' => true]);
    }

    /**
     * Returns action url for authentication form
     *
     * @return string
     */
    public function getAuthenticateFormAction()
    {
        return $this->getUrl('frontend2fa/account/authenticate', ['_secure' => true]);
    }

    /**
     * @param null $customer
     * @return bool
     */
    public function is2faConfiguredForCustomer($customer = null)
    {
        if ($customer === null) {
            $customer = $this->customerSession->getCustomer();
        }
        return $this->observer->is2faConfiguredForCustomer($customer);
    }
}