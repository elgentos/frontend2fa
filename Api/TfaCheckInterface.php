<?php

/**
 * Copyright Youwe. All rights reserved.
 * https://www.youweagency.com
 */

declare(strict_types=1);

namespace Elgentos\Frontend2FA\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;

interface TfaCheckInterface
{

    const FRONTEND_2_FA_ACCOUNT_SETUP_ROUTE = 'customer_account_setup';
    const FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_ROUTE = 'customer_account_authenticate';
    const CUSTOMER_ACCOUNT_LOGOUT_ROUTE = 'customer_account_logout';
    const FRONTEND_2_FA_ACCOUNT_SETUP_PATH = 'customer/account/setup';
    const FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_PATH = 'customer/account/authenticate';

    public function isCustomerInForced2faGroup(\Magento\Customer\Model\Customer $customer): bool;

    public function is2faConfiguredForCustomer(\Magento\Customer\Model\Customer $customer): bool;

    public function getAllowedRoutes(\Magento\Customer\Model\Customer $customer): array;

}
