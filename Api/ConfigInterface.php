<?php

/**
 * Copyright Youwe. All rights reserved.
 * https://www.youweagency.com
 */

declare(strict_types=1);

namespace Elgentos\Frontend2FA\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;

interface ConfigInterface
{
    public function isEnabled(): bool;
    public function getForced2faCustomerGroups(): array;
}
