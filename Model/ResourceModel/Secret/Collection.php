<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 5-3-19
 * Time: 16:29
 */

namespace Elgentos\Frontend2FA\Model\ResourceModel\Secret;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'secret_id';

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Elgentos\Frontend2FA\Model\Secret', 'Elgentos\Frontend2FA\Model\ResourceModel\Secret');
    }

}