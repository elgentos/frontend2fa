<?php

namespace Elgentos\Frontend2FA\Controller\Adminhtml\Authenticator;

use Elgentos\Frontend2FA\Model\SecretFactory;
use Magento\Backend\App\Action;

class Reset extends \Magento\Backend\App\Action
{
    /**
     * @var SecretFactory
     */
    public $secretFactory;

    /**
     * Reset constructor.
     * @param Action\Context $context
     * @param SecretFactory $secretFactory
     */
    public function __construct(
        Action\Context $context,
        SecretFactory $secretFactory
    ) {
        parent::__construct($context);
        $this->secretFactory = $secretFactory;
    }

    public function execute()
    {
        $customerId = $this->getRequest()->getParam('customer_id');
        $secret = $this->secretFactory->create()->load($customerId, 'customer_id');
        if ($secret->getId()) {
            $secret->delete();
            $this->messageManager->addSuccessMessage(__('Frontend 2FA for customer has been reset.'));
        } else {
            $this->messageManager->addNoticeMessage(__('Frontend 2FA for customer has never been set.'));
        }
        $this->_redirect('customer/index/edit', ['id' => $customerId]);
    }

}