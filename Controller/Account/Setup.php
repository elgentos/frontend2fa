<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Elgentos\Frontend2FA\Controller\Account;

use Elgentos\Frontend2FA\Model\SecretFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\LayoutFactory;

class Setup extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    protected $secretFactory;
    protected $_layoutFactory;

    /**
     * @param Context $context
     * @param LayoutFactory $layoutFactory
     * @param Session $customerSession
     * @param SecretFactory $secretFactory
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        LayoutFactory $layoutFactory,
        SecretFactory $secretFactory
    ) {
        $this->_layoutFactory = $layoutFactory;
        parent::__construct($context);
        $this->_customerSession = $customerSession;
        $this->secretFactory = $secretFactory;
    }

    /**
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
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
     * @throws \Exception
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_view->loadLayout();
            $this->_view->getPage()->getConfig()->getTitle()->set(__('Two-Factor Authentication Setup'));
            $this->_view->renderLayout();
        } else {
            $authenticator = $this->_layoutFactory->create()->createBlock('Elgentos\Frontend2FA\Block\Authenticator');
            if ($authenticator->authenticateQRCode($post['secret'], $post['code'])) {
                $this->messageManager->addSuccessMessage(__('2FA successfully set up'));
                $this->secretFactory->create()->setData([
                    'customer_id' => $this->_customerSession->getCustomerId(),
                    'secret' => $authenticator->getSecretCode()
                ])->save();
                $this->_customerSession->set2faSuccessful(true);
                $this->_redirect('customer/account');
            } else {
                $this->messageManager->addErrorMessage(
                    __('Invalid 2FA Authentication Code')
                );
                $this->_redirect('frontend2fa/account/setup');
            }
            return;
        }
    }
}
