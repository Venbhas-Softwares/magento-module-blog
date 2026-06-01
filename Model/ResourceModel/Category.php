<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\ResourceModel;

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
     * @return \Venbhas\Blog\Model\ResourceModel\Category
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_afterSave($object);
        if (!$object->getId()) {
            $object->setId($this->getConnection()->lastInsertId($this->getMainTable()));
        }
        return $this;
    }

    /**
     * Get category id by url key.
     *
     * @param string $urlKey
     * @return int|null
     */
    public function getIdByUrlKey(string $urlKey): ?int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'category_id')
            ->where('url_key = ?', $urlKey)
            ->limit(1);
        $id = $connection->fetchOne($select);

        return $id !== false ? (int) $id : null;
    }
}
