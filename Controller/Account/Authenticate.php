<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Elgentos\Frontend2FA\Controller\Account;

use Elgentos\Frontend2FA\Model\SecretFactory;
use Magento\Framework\App\RequestInterface;

class Authenticate extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Neyamtux\Authenticator\Lib\PHPGangsta\GoogleAuthenticator
     */
    public $googleAuthenticator;
    /**
     * @var SecretFactory
     */
    public $secretFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @param \Magento\Framework\App\Action\Context                      $context
     * @param \Magento\Customer\Model\Session                            $customerSession
     * @param \Neyamtux\Authenticator\Lib\PHPGangsta\GoogleAuthenticator $googleAuthenticator
     * @param SecretFactory                                              $secretFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Neyamtux\Authenticator\Lib\PHPGangsta\GoogleAuthenticator $googleAuthenticator,
        SecretFactory $secretFactory
    ) {
        $this->_customerSession = $customerSession;
        parent::__construct($context);
        $this->googleAuthenticator = $googleAuthenticator;
        $this->secretFactory = $secretFactory;
    }

    /**
     * @param RequestInterface $request
     *
     * @throws \Magento\Framework\Exception\NotFoundException
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->_objectManager->get(\Magento\Customer\Model\Url::class)->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_view->loadLayout();
            $this->_view->getPage()->getConfig()->getTitle()->set(__('Two-Factor Authentication'));
            $this->_view->renderLayout();
        } else {
            $secret = $this->secretFactory->create()->load($this->_customerSession->getCustomerId(), 'customer_id')->getSecret();
            if ($this->_authenticateQRCode($secret, $post['code'])) {
                $this->messageManager->addSuccessMessage(__('Two Factor Authentication successful'));
                $this->_customerSession->set2faSuccessful(true);
                $this->_redirect($this->_customerSession->getBefore2faUrl() ?? 'customer/account');
            } else {
                $this->messageManager->addErrorMessage(__('Two Factor Authentication code incorrect'));
                $this->_customerSession->set2faSuccessful(false);
                $this->_redirect('*/*/*');
            }
        }
    }

    /**
     * Authenticates QR code.
     *
     * @param $secret
     * @param $code
     * @param int $clockTolerance
     *
     * @return string
     */
    private function _authenticateQRCode($secret, $code, $clockTolerance = 2)
    {
        if (!$secret || !$code) {
            return false;
        }

        return $this->googleAuthenticator->verifyCode($secret, $code, $clockTolerance);
    }
}
