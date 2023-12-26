<?php

/**
 * Copyright Youwe. All rights reserved.
 * https://www.youweagency.com
 */

declare(strict_types=1);

namespace Elgentos\Frontend2FA\Model;

use Elgentos\Frontend2FA\Api\ConfigInterface;
use Elgentos\Frontend2FA\Api\TfaCheckInterface;
use Elgentos\Frontend2FA\Model\SecretFactory;

class TfaCheck implements TfaCheckInterface
{
    public function __construct(
        private readonly SecretFactory $secretFactory,
        private readonly ConfigInterface $config
    )
    {

    }

    public function isCustomerInForced2faGroup(\Magento\Customer\Model\Customer $customer):bool
    {
        return in_array($customer->getGroupId(), $this->config->getForced2faCustomerGroups());
    }


    public function is2faConfiguredForCustomer(\Magento\Customer\Model\Customer $customer): bool
    {
        $secret = $this->secretFactory->create()->load($customer->getId(), 'customer_id');
        if ($secret->getId() && $secret->getSecret()) {
            return true;
        }

        return false;
    }

    public function getAllowedRoutes(\Magento\Customer\Model\Customer $customer): array
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
