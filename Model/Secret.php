<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 5-3-19
 * Time: 16:29
 */

namespace Elgentos\Frontend2FA\Model;

use Elgentos\Frontend2FA\Model\ResourceModel\Secret as SecretResourceModel;
use Elgentos\Frontend2FA\Model\ResourceModel\Secret\Collection as SecretCollection;

/**
 * @method SecretResourceModel getResource()
 * @method SecretCollection getCollection()
 */
class Secret extends \Magento\Framework\Model\AbstractModel implements \Elgentos\Frontend2FA\Api\Data\SecretInterface,
    \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'elgentos_frontend2fa_secret';
    protected $_cacheTag = 'elgentos_frontend2fa_secret';
    protected $_eventPrefix = 'elgentos_frontend2fa_secret';

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Elgentos\Frontend2FA\Model\ResourceModel\Secret');
    }

    /**
     * @return array|string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}