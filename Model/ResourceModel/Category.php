<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\ResourceModel;

use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
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
     * Ensure the model gets the new category_id after insert.
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        parent::_afterSave($object);

        if (!$object->getId()) {
            $object->setId($this->getConnection()->lastInsertId($this->getMainTable()));
        }

        $this->updatePathAndLevel($object);
        $this->updateChildrenCount((int) $object->getData('parent_id'));

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _afterDelete(AbstractModel $object)
    {
        parent::_afterDelete($object);
        $this->updateChildrenCount((int) $object->getData('parent_id'));

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

    /**
     * Get direct children count.
     *
     * @param int $parentId
     * @return int
     */
    public function getChildrenCount(int $parentId): int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), [new Expression('COUNT(*)')])
            ->where('parent_id = ?', $parentId);

        return (int) $connection->fetchOne($select);
    }

    /**
     * Move category under a new parent and optional position.
     *
     * @param int $categoryId
     * @param int $newParentId
     * @param int|null $afterCategoryId
     * @return void
     * @throws LocalizedException
     */
    public function moveCategory(int $categoryId, int $newParentId, ?int $afterCategoryId = null): void
    {
        if ($categoryId <= 0) {
            throw new LocalizedException(__('Invalid category.'));
        }

        if ($newParentId === $categoryId) {
            throw new LocalizedException(__('Cannot move category under itself.'));
        }

        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $category = $connection->fetchRow(
            $connection->select()->from($table)->where('category_id = ?', $categoryId)
        );
        if (!$category) {
            throw new LocalizedException(__('Category no longer exists.'));
        }

        $oldParentId = (int) ($category['parent_id'] ?? 0);
        $oldPath = (string) ($category['path'] ?? '');

        if ($newParentId > 0) {
            $parent = $connection->fetchRow(
                $connection->select()->from($table)->where('category_id = ?', $newParentId)
            );
            if (!$parent) {
                throw new LocalizedException(__('Parent category no longer exists.'));
            }
            $parentPath = (string) ($parent['path'] ?? '');
            if ($parentPath !== '' && strpos($parentPath . '/', $oldPath . '/') === 0) {
                throw new LocalizedException(__('Cannot move category into its own descendant.'));
            }
        }

        $connection->update(
            $table,
            ['parent_id' => max(0, $newParentId)],
            ['category_id = ?' => $categoryId]
        );

        $model = new \Magento\Framework\DataObject(['category_id' => $categoryId, 'parent_id' => $newParentId]);
        $this->updatePathAndLevel($model);

        if ($afterCategoryId !== null && $afterCategoryId > 0) {
            $this->reorderSiblings($newParentId, $categoryId, $afterCategoryId);
        }

        $this->updateChildrenCount($oldParentId);
        $this->updateChildrenCount($newParentId);
    }

    /**
     * Update path and level for category and descendants.
     *
     * @param AbstractModel $object
     * @return void
     */
    private function updatePathAndLevel(AbstractModel $object): void
    {
        $categoryId = (int) $object->getId();
        if ($categoryId <= 0) {
            return;
        }

        $connection = $this->getConnection();
        $table = $this->getMainTable();
        $parentId = (int) $object->getData('parent_id');

        if ($parentId > 0) {
            $parent = $connection->fetchRow(
                $connection->select()->from($table, ['path', 'level'])->where('category_id = ?', $parentId)
            );
            if (!$parent) {
                $parentId = 0;
            }
        }

        if ($parentId > 0 && !empty($parent)) {
            $path = trim((string) $parent['path']) . '/' . $categoryId;
            $level = (int) $parent['level'] + 1;
        } else {
            $parentId = 0;
            $path = (string) $categoryId;
            $level = 1;
        }

        $current = $connection->fetchRow(
            $connection->select()->from($table, ['path', 'parent_id'])->where('category_id = ?', $categoryId)
        );
        $oldPath = (string) ($current['path'] ?? '');

        $connection->update(
            $table,
            [
                'parent_id' => $parentId,
                'path' => $path,
                'level' => $level,
            ],
            ['category_id = ?' => $categoryId]
        );

        if ($oldPath !== '' && $oldPath !== $path) {
            $descendants = $connection->fetchAll(
                $connection->select()->from($table)->where('path LIKE ?', $oldPath . '/%')
            );
            foreach ($descendants as $row) {
                $newChildPath = str_replace($oldPath . '/', $path . '/', (string) $row['path']);
                $childLevel = count(array_filter(explode('/', $newChildPath)));
                $connection->update(
                    $table,
                    ['path' => $newChildPath, 'level' => $childLevel],
                    ['category_id = ?' => (int) $row['category_id']]
                );
            }
        }

        $object->setData('parent_id', $parentId);
        $object->setData('path', $path);
        $object->setData('level', $level);
    }

    /**
     * Update children_count for a parent category.
     *
     * @param int $parentId
     * @return void
     */
    private function updateChildrenCount(int $parentId): void
    {
        if ($parentId <= 0) {
            return;
        }

        $count = $this->getChildrenCount($parentId);
        $this->getConnection()->update(
            $this->getMainTable(),
            ['children_count' => $count],
            ['category_id = ?' => $parentId]
        );
    }

    /**
     * Reorder siblings after drag-and-drop.
     *
     * @param int $parentId
     * @param int $categoryId
     * @param int $afterCategoryId
     * @return void
     */
    private function reorderSiblings(int $parentId, int $categoryId, int $afterCategoryId): void
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $siblings = $connection->fetchCol(
            $connection->select()
                ->from($table, 'category_id')
                ->where('parent_id = ?', $parentId)
                ->where('category_id != ?', $categoryId)
                ->order('position ASC')
                ->order('category_id ASC')
        );

        $ordered = [];
        foreach ($siblings as $siblingId) {
            $siblingId = (int) $siblingId;
            $ordered[] = $siblingId;
            if ($siblingId === $afterCategoryId) {
                $ordered[] = $categoryId;
            }
        }

        if (!in_array($categoryId, $ordered, true)) {
            $ordered[] = $categoryId;
        }

        $position = 0;
        foreach ($ordered as $id) {
            $connection->update($table, ['position' => $position++], ['category_id = ?' => $id]);
        }
    }
}
