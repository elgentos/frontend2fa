<?php

declare(strict_types=1);

namespace Elgentos\Frontend2FA\Plugin;

use Magento\Customer\Model\Account\Redirect;
use Magento\Customer\Model\Session as CustomerSession;

class AccountRedirectPlugin
{
    public function __construct(private readonly CustomerSession $customerSession)
    {
    }

    public function afterGetRedirectCookie(Redirect $subject, ?string $result): ?string
    {
        if ($result && !$this->customerSession->getBefore2faUrl(false)) {
            $this->customerSession->setBefore2faUrl($result);
        }

        return $result;
    }
}
