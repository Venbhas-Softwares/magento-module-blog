<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Comment resource model.
 */
class Comment extends AbstractDb
{
    /**
     * Initialize comment resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('venbhas_article_comment', 'comment_id');
    }

    /**
     * Set id after insert for new records.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_afterSave($object);
        if (!$object->getId()) {
            $object->setId($this->getConnection()->lastInsertId($this->getMainTable()));
        }
        return $this;
    }
}
