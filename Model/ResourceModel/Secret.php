<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 5-3-19
 * Time: 16:29
 */

namespace Elgentos\Frontend2FA\Model\ResourceModel;

class Secret extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    protected function _construct()
    {
        $this->_init('elgentos_frontend2fa_secrets', 'secret_id');
    }

}