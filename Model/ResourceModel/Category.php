<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Category resource model.
 */
class Category extends AbstractDb
{
    /**
     * Initialize category resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('venbhas_article_category', 'category_id');
    }

    /**
     * Ensure the model gets the new category_id after insert (fixes FK on related_products).
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return \Venbhas\Article\Model\ResourceModel\Category
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
