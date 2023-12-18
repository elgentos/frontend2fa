<?php

/**
 * Copyright Youwe. All rights reserved.
 * https://www.youweagency.com
 */

declare(strict_types=1);

namespace Elgentos\Frontend2FA\Model;

use Elgentos\Frontend2FA\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config implements ConfigInterface
{
    const ELGENTOS_AUTHENTICATOR_GENERAL_ENABLE = 'elgentos_authenticator/general/enable';
    const ELGENTOS_AUTHENTICATOR_GENERAL_FORCED_GROUPS = 'elgentos_authenticator/general/forced_groups';

    public function __construct(
        private readonly ScopeConfigInterface $config
    ){

    }

    public function isEnabled(): bool
    {
        return $this->config->isSetFlag(
            self::ELGENTOS_AUTHENTICATOR_GENERAL_ENABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getForced2faCustomerGroups(): array
    {
        $forced2faCustomerGroups = $this->config->getValue(self::ELGENTOS_AUTHENTICATOR_GENERAL_FORCED_GROUPS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE) ?? '';

        return array_filter(array_map('trim', explode(',', $forced2faCustomerGroups)));
    }
}
