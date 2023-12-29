<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 5-3-19
 * Time: 13:36.
 */

namespace Elgentos\Frontend2FA\Block;

use Elgentos\Frontend2FA\Api\ConfigInterface;
use Elgentos\Frontend2FA\Api\TfaCheckInterface;
use Elgentos\Frontend2FA\Model\GoogleAuthenticatorService;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Neyamtux\Authenticator\Lib\PHPGangsta\GoogleAuthenticator;

class Authenticator extends \Neyamtux\Authenticator\Block\Authenticator
{
    /**
     * @var Session
     */
    public $customerSession;
    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var GoogleAuthenticatorService
     */
    public $googleAuthenticatorService;

    /**
     * Authenticator constructor.
     *
     * @param Context               $context
     * @param GoogleAuthenticator   $googleAuthenticator
     * @param CatalogSession        $session
     * @param TfaCheckInterface     $tfaCheck
     * @param Session               $customerSession
     * @param StoreManagerInterface $storeManager
     * @param array                 $data
     */
    public function __construct(
        Context $context,
        GoogleAuthenticator $googleAuthenticator,
        GoogleAuthenticatorService $googleAuthenticatorService,
        CatalogSession $session,
        private readonly TfaCheckInterface $tfaCheck,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        private readonly ConfigInterface $config,
        array $data = []
    ) {
        parent::__construct($context, $googleAuthenticator, $session, $data);
        $this->googleAuthenticatorService = $googleAuthenticatorService;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @return string
     */
    public function getQrCodeBase64Image()
    {
        // Replace non-alphanumeric characters with dashes; Google Authenticator does not like spaces in the title
        $title = sprintf("%s %s: %s",
            $this->storeManager->getWebsite()->getName(),
            '2FA',
            $this->customerSession->getCustomer()->getEmail()
        );
        $imageData = base64_encode($this->googleAuthenticatorService->getQrCodeEndroid($title, $this->_googleSecret));

        return 'data:image/png;base64,'.$imageData;
    }

    /**
     * Returns action url for authentication form.
     *
     * @return string
     */
    public function getSetupFormAction()
    {
        return $this->getUrl(TfaCheckInterface::FRONTEND_2_FA_ACCOUNT_SETUP_PATH, ['_secure' => true]);
    }

    /**
     * Returns action url for authentication form.
     *
     * @return string
     */
    public function getAuthenticateFormAction()
    {
        return $this->getUrl(TfaCheckInterface::FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_PATH, ['_secure' => true]);
    }

    /**
     * @param null $customer
     *
     * @return bool
     */
    public function is2faConfiguredForCustomer($customer = null)
    {
        if ($customer === null) {
            $customer = $this->customerSession->getCustomer();
        }

        return $this->tfaCheck->is2faConfiguredForCustomer($customer);
    }

    public function getCancelSetupUrl()
    {
        return '/customer/account/';
    }

    public function getCancel2faUrl()
    {
        return '/customer/account/logout/';
    }

    public function isInForcedGroup(): bool {
        $customer = $this->customerSession->getCustomer();
        return in_array(
            $customer->getGroupId(),
            $this->config->getForced2faCustomerGroups()
        );
    }
}
