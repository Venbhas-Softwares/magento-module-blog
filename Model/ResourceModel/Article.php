<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Article extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('venbhas_article', 'article_id');
    }

    /**
     * Ensure the model gets the new article_id after insert (fixes FK on related_products).
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
