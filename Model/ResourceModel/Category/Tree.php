<?php
declare(strict_types=1);

namespace Venbhas\Blog\Model\ResourceModel\Category;

use Magento\Framework\Data\Tree as DataTree;
use Magento\Framework\Data\Tree\Node;
use Venbhas\Blog\Model\Category as CategoryModel;
use Venbhas\Blog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Blog category tree for admin UI.
 */
class Tree
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var DataTree|null */
    private $tree;

    /** @var Node|null */
    private $root;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Load category tree with virtual root node.
     *
     * @param int $recursionLevel
     * @return $this
     */
    public function load(int $recursionLevel = 3): self
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect(
            ['category_id', 'name', 'parent_id', 'path', 'level', 'position', 'children_count', 'status']
        )->setOrder('position', 'ASC')->setOrder('category_id', 'ASC');

        $nodes = [];
        $this->tree = new DataTree();
        $this->root = new Node(
            [
                'category_id' => CategoryModel::TREE_ROOT_ID,
                'name' => (string) __('Blog Categories'),
                'parent_id' => null,
                'level' => 0,
                'children_count' => 0,
                'is_active' => 1,
            ],
            'category_id',
            $this->tree
        );
        $this->tree->addNode($this->root);
        $nodes[CategoryModel::TREE_ROOT_ID] = $this->root;

        foreach ($collection as $category) {
            $node = new Node($category->getData(), 'category_id', $this->tree);
            $nodes[(int) $category->getId()] = $node;
        }

        foreach ($collection as $category) {
            $categoryId = (int) $category->getId();
            $parentId = (int) $category->getParentId();
            if ($parentId <= 0 || !isset($nodes[$parentId])) {
                $parentId = CategoryModel::TREE_ROOT_ID;
            }
            $nodes[$parentId]->addChild($nodes[$categoryId]);
        }

        return $this;
    }

    /**
     * Get virtual root node.
     *
     * @return Node|null
     */
    public function getRoot(): ?Node
    {
        return $this->root;
    }

    /**
     * Get node by category id.
     *
     * @param int $categoryId
     * @return Node|null
     */
    public function getNodeById(int $categoryId): ?Node
    {
        if ($categoryId === CategoryModel::TREE_ROOT_ID) {
            return $this->root;
        }

        return $this->root ? $this->findNode($this->root, $categoryId) : null;
    }

    /**
     * Load subtree for AJAX requests.
     *
     * @param int $parentId
     * @param int $recursionLevel
     * @return Node|null
     */
    public function loadNode(int $parentId, int $recursionLevel = 2): ?Node
    {
        $this->load($recursionLevel);
        return $this->getNodeById($parentId);
    }

    /**
     * Recursively locate a category node in the tree.
     *
     * @param Node $node
     * @param int $categoryId
     * @return Node|null
     */
    private function findNode(Node $node, int $categoryId): ?Node
    {
        if ((int) $node->getId() === $categoryId) {
            return $node;
        }

        foreach ($node->getChildren() as $child) {
            $found = $this->findNode($child, $categoryId);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }
}
